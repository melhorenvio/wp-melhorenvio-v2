<?php

namespace Services;

use Helpers\TimeHelper;
use Helpers\MoneyHelper;
use Helpers\DimensionsHelper;
use Helpers\PostalCodeHelper;

class QuotationProductPageService
{
    /**
     * No requirement to have free shipping
     */
    const FREE_SHIPPING = 'free_shipping';

    /**
     * Minimum order value for free shipping
     */
    const FREE_SHIPPING_MIN_AMOUNT = 'min_amount';

    /**
     * Requirement to have free shipping coupon and minimum order value
     */
    const FREE_SHIPPIING_COUPOM_AND_MIN_AMOUNT = 'both';

    /**
     * Requirement to have free shipping coupon
     */
    const FREE_SHIPPING_COUPOM = 'either';

    /**
     * Requirement to have free shipping coupon or minimum order value
     */
    const FREE_SHIPPING_COUPON_OR_MIN_AMOUNT = 'coupon';

    /**
     * A woocommerce product
     *
     * @var object
     */
    protected $product;

    /**
     * Buyer postal code
     *
     * @var float
     */
    protected $postalCode;

    /**
     * product quantity
     *
     * @var int
     */
    protected $quantity;

    /**
     * Address information using the location service based on the informed postal code
     *
     * @var object
     */
    protected $destination;

    /**
     * A standard array package used in the WooCommerce shopping cart.
     *
     * @var aray
     */
    protected $package;

    /**
     * Shipping methods returned from WooCommerce based on the informed package and postalcode.
     *
     * @var array
     */
    protected $shippingMethods;

    /**
     * Service Builder
     *
     * @param int $productId
     * @param float $postalCode
     * @param int $quantity
     */
    public function __construct($productId, $postalCode, $quantity)
    {
        $this->product = wc_get_product($productId);

        $this->postalCode = PostalCodeHelper::postalcode($postalCode);

        $this->quantity = intval($quantity);

        $this->destination = (new LocationService())->getAddressByPostalCode($this->postalCode);
    }

    /**
     * Function to search the WooCommerce shipping methods for the product 
     * and the zones chosen by the seller.
     *
     * @return array
     */
    public function getRatesShipping()
    {
        if (empty($this->product)) {
            return [
                'success' => false,
                'error' => "Não encontramos o produto na base de dados"
            ];
        }

        if (empty($this->destination)) {
            return [
                'success' => false,
                'error' => sprintf(
                    "Não encontramos um endereço válido para o CEP %d",
                    $this->postalCode
                )
            ];
        }

        if (!is_int($this->quantity) || $this->quantity == 0) {
            return [
                'success' => false,
                'error' => "É necessário informar uma quantidade válida"
            ];
        }

        $this->createPackageToCalculate();

        $this->getShippingMethodsByPackage();

        if (count($this->shippingMethods) == 0) {
            return [
                'success' => false,
                'error' => "Não existem métodos de envios para esse produto ou endereço."
            ];
        }

        $this->filterRateByShippingMethods();

        $this->orderingRatesByPrice();

        $result = [];
        foreach ($this->rates as $rate) {
            if (!empty($rate)) {
                $result[] = $rate;
            }
        }
        return $result;
    }

    /**
     * Function to create the package in the patterns used in WooCommerce 
     * to make requests for WooCommerce native delivery methods and zones.
     *
     * @return void
     */
    private function createPackageToCalculate()
    {
        $this->package = [
            'ship_via'     => '',
            'destination'  => [
                'country'  => 'BR',
                'state'    => $this->destination->uf,
                'postcode' => $this->destination->cep,
            ],
            'cotationProduct' => [
                (object) [
                    'id' => $this->product->get_id(),
                    'shipping_class_id' => $this->product->get_shipping_class_id(),
                    'weight' => DimensionsHelper::convertWeightUnit(
                        floatval($this->product->get_weight())
                    ),
                    'width' => DimensionsHelper::convertUnitDimensionToCentimeter(
                        floatval($this->product->get_width())
                    ),
                    'length' => DimensionsHelper::convertUnitDimensionToCentimeter(
                        floatval($this->product->get_length())
                    ),
                    'height' => DimensionsHelper::convertUnitDimensionToCentimeter(
                        floatval($this->product->get_height())
                    ),
                    'quantity' => $this->quantity,
                    'price' => floatval(
                        $this->product->get_price()
                    ),
                    'insurance_value'    => floatval(
                        $this->product->get_price()
                    ),
                    'notConverterWeight' => true
                ]
            ]
        ];
    }

    /**
     * Function to obtain the shipping methods available for the created package.
     *
     * @return void
     */
    private function getShippingMethodsByPackage()
    {
        $shippingZone = \WC_Shipping_Zones::get_zone_matching_package($this->package);

        $shippingMethods = $shippingZone->get_shipping_methods(true);

        if ($this->product) {
            $productShippingClassId = $this->product->get_shipping_class_id();

            if ($productShippingClassId) {
                foreach ($shippingMethods as $key => $method) {
                    if (empty($method->instance_settings['shipping_class_id'])) {
                        continue;
                    }

                    if ($method->instance_settings['shipping_class_id'] == CalculateShippingMethodService::ANY_DELIVERY) {
                        continue;
                    }

                    if ($productShippingClassId != $method->instance_settings['shipping_class_id']) {
                        unset($shippingMethods[$key]);
                    }
                }
            }
        }

        $this->shippingMethods = $shippingMethods;
    }

    /**
     * Function to map shipping methods and enter 
     * necessary information for the product screen calculator.
     *
     * @return void
     */
    private function filterRateByShippingMethods()
    {
        $this->rates = array_map(function ($shippingMethod) {

            $rate = $shippingMethod->get_rates_for_package($this->package);

            $rate = end($rate);

            if (!empty($rate)) {
                return [
                    'id' => $shippingMethod->id,
                    'name' => $shippingMethod->title,
                    'price' => (!empty((string) $rate->meta_data['price']))
                        ? $rate->meta_data['price']
                        : MoneyHelper::price($rate->get_cost(), 0, 0),
                    'delivery_time' => (!empty((string) $rate->meta_data['delivery_time']))
                        ? $rate->meta_data['delivery_time']
                        : null,
                ];
            }
        }, $this->shippingMethods);

        $this->showFreeShippingMethod();
    }

    /**
     * Function to manage free shipping on the product screen. 
     * the native free shipping of WooCommerce is based on the total value of the cart, 
     * as the cart is probably empty on the product screen, 
     * it is necessary to modify the standard operation of the free shipping of WooCommerce.
     *
     * @return void
     */
    private function showFreeShippingMethod()
    {
        $free = array_filter($this->shippingMethods, function ($item) {
            if ($item->id == self::FREE_SHIPPING) {
                return $item;
            }
        });

        if (!empty($free)) {
            $labelFreeShippig = $this->rateForFreeShipping($free);

            if (!empty($labelFreeShippig)) {
                $this->rates[] = [
                    'id' => self::FREE_SHIPPING,
                    'name' => ($labelFreeShippig == 'Frete Grátis')
                        ? end($free)->title
                        : sprintf("¹%s", end($free)->title),
                    'price' => 'R$0,00',
                    'delivery_time' => null,
                    'observations' => $labelFreeShippig
                ];
            }
        }
    }

    /**
     * Function to set the type of free shipping
     *
     * @param array $free
     * @return string|bool
     */
    private function rateForFreeShipping($free)
    {
        $labelFreeShippig = null;

        $freeShipping = end($free);

        if (empty($freeShipping->requires)) {
            $labelFreeShippig = 'Frete Grátis';
        }

        if (!empty($freeShipping->requires) && !empty($freeShipping->min_amount)) {
            $labelFreeShippig = sprintf(
                "¹Frete grátis com valor mínimo de %s",
                MoneyHelper::price($freeShipping->min_amount, 0, 0)
            );
        }

        if ($freeShipping->requires == self::FREE_SHIPPING_MIN_AMOUNT && !empty($freeShipping->min_amount)) {
            $labelFreeShippig = sprintf(
                "¹Frete grátis para pedidos com valor mínimo de %s",
                MoneyHelper::price($freeShipping->min_amount, 0, 0)
            );
        }

        if ($freeShipping->requires == self::FREE_SHIPPIING_COUPOM_AND_MIN_AMOUNT && !empty($freeShipping->min_amount)) {
            $labelFreeShippig = sprintf(
                "¹Frete grátis para utilização de coupom grátis para pedidos mínimos de %s",
                MoneyHelper::price($freeShipping->min_amount, 0, 0)
            );
        }

        if ($freeShipping->requires == self::FREE_SHIPPING_COUPOM) {
            $labelFreeShippig = "¹Frete grátis para utilização de coupom grátis";
        }

        if ($freeShipping->requires == self::FREE_SHIPPING_MIN_AMOUNT && !empty($freeShipping->min_amount)) {
            $labelFreeShippig = sprintf(
                "¹Frete grátis para utilização de coupom com valor mínimo de pedido de %s",
                MoneyHelper::price($freeShipping->min_amount, 0, 0)
            );
        }

        return $labelFreeShippig;
    }

    /**
     * Function to sort the rates by price
     *
     * @return array
     */
    public function orderingRatesByPrice()
    {
        uasort($this->rates, function ($a, $b) {
            if ($a == $b) return 0;
            return ($a['price'] < $b['price']) ? -1 : 1;
        });

        return array_values($this->rates);
    }
}
