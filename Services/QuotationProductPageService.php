<?php

namespace Services;

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
    const FREE_SHIPPIING_COUPON_AND_MIN_AMOUNT = 'both';

    /**
     * Requirement to have free shipping coupon
     */
    const FREE_SHIPPING_COUPON = 'either';

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
     * @var string
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
     * @param string $postalCode
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
                    "Não encontramos um endereço válido para o CEP %s",
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

        $this->setAddressUserForWooCommerce();

        $this->createPackageToCalculate();

        $this->setShippingMethodsByPackage();

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
        $contents = [];

        $contents[$this->product->id] = [
            'data' => $this->product,
            'quantity' => $this->quantity
        ];

        $this->package = [
            'ship_via'     => '',
            'destination'  => [
                'country'  => 'BR',
                'state'    => $this->destination->uf,
                'postcode' => $this->destination->cep,
            ],
            'contents' => $contents,
        ];
    }

    /**
     * Function to define the user's address obtained in the Melhor Envio to woocommerce
     *
     * @return void
     */
    private function setAddressUserForWooCommerce()
    {
        global $woocommerce;

        $woocommerce->customer->set_shipping_postcode( $this->destination->cep );
        $woocommerce->customer->set_postcode( $this->destination->cep );

        $woocommerce->customer->set_shipping_city( $this->destination->cidade );
        $woocommerce->customer->set_city( $this->destination->cidade );

        $woocommerce->customer->set_shipping_state( $this->destination->uf );
        $woocommerce->customer->set_state( $this->destination->uf );

        $woocommerce->customer->set_shipping_address( $this->destination->logradouro );
        $woocommerce->customer->set_address( $this->destination->logradouro );
    }

    /**
     * Function to obtain the shipping methods available for the created package.
     *
     * @return void
     */
    private function setShippingMethodsByPackage()
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

            if (!empty($rate) && $rate->method_id != self::FREE_SHIPPING) {

                $delivery_time = null;

                if (!empty((string) $rate->meta_data['delivery_time'])) {
                    $delivery_time = $rate->meta_data['delivery_time'];
                }

                if (!empty((string) $rate->meta_data['_delivery_forecast'])) {
                    $delivery_time = ($rate->meta_data['_delivery_forecast'] == 1) 
                        ? "(1 dia útil)" 
                        : sprintf("(%s dias úteis)", $rate->meta_data['_delivery_forecast']) ;
                }

                return [
                    'id' => $shippingMethod->id,
                    'name' => $shippingMethod->title,
                    'cost' => (!empty((string) $rate->meta_data['price']))
                        ? $rate->meta_data['price']
                        : MoneyHelper::cost($rate->get_cost(), 0, 0),
                    'price' => (!empty((string) $rate->meta_data['price']))
                        ? $rate->meta_data['price']
                        : MoneyHelper::price($rate->get_cost(), 0, 0),
                    'delivery_time' => $delivery_time
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

            $labelFreeShipping = $this->rateForFreeShipping($free);

            if (!empty($labelFreeShipping)) {
                $this->rates[] = [
                    'id' => self::FREE_SHIPPING,
                    'name' => ($labelFreeShipping == 'Frete Grátis')
                        ? end($free)->title
                        : sprintf("¹%s", end($free)->title),
                    'price' => 'R$0,00',
                    'cost' => 0,
                    'delivery_time' => null,
                    'observations' => $labelFreeShipping
                ];
            }
        }
    }

    /**
     * Function to set the type of free shipping
     *
     * @param array $free
     * @return string
     */
    private function rateForFreeShipping($free)
    {
        $freeShipping = end($free);

        if (!empty($freeShipping->requires) && !empty($freeShipping->min_amount)) {
            return sprintf(
                "¹Frete grátis com valor mínimo de %s",
                MoneyHelper::price($freeShipping->min_amount, 0, 0)
            );
        }

        if ($freeShipping->requires == self::FREE_SHIPPING_MIN_AMOUNT && !empty($freeShipping->min_amount)) {
            return sprintf(
                "¹Frete grátis para pedidos com valor mínimo de %s",
                MoneyHelper::price($freeShipping->min_amount, 0, 0)
            );
        }

        if ($freeShipping->requires == self::FREE_SHIPPIING_COUPON_AND_MIN_AMOUNT && !empty($freeShipping->min_amount)) {
            return sprintf(
                "¹Frete grátis para utilização de cupom grátis para pedidos mínimos de %s",
                MoneyHelper::price($freeShipping->min_amount, 0, 0)
            );
        }

        if ($freeShipping->requires == self::FREE_SHIPPING_COUPON) {
            return "¹Frete grátis para utilização de cupom grátis";
        }

        if ($freeShipping->requires == self::FREE_SHIPPING_MIN_AMOUNT && !empty($freeShipping->min_amount)) {
            return sprintf(
                "¹Frete grátis para utilização de cupom com valor mínimo de pedido de %s",
                MoneyHelper::price($freeShipping->min_amount, 0, 0)
            );
        }

        return 'Frete Grátis';
    }

    /**
     * Function to sort the rates by price
     *
     * @return array
     */
    public function orderingRatesByPrice()
    {
        uasort($this->rates, function ($a, $b) {
            if ($a === $b) return 0;
            return ($a['price'] < $b['price']) ? -1 : 1;
        });

        return array_values($this->rates);
    }
}
