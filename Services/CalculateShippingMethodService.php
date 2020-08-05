<?php

namespace Services;

use Helpers\MoneyHelper;
use Helpers\OptionsHelper;
use Helpers\TimeHelper;

class CalculateShippingMethodService
{
    /**
     * Constant for delivery class of any class
     */
    const ANY_DELIVERY = -1;

    /**
     * Constant for no delivery class
     */

    const WITHOUT_DELIVERY = 0;

    const SERVICES_CORREIOS = ['1', '2', '17'];

    /**
     * Function to carry out the freight quote in the Melhor Envio api.
     *
     * @param array $package
     * @param int $code
     * @param int $id
     * @param string $company
     * @return void
     */
    public function calculate_shipping($package = [], $code, $id, $company)
    {
        $to = preg_replace('/\D/', '', $package['destination']['postcode']);

        $products = (isset($package['cotationProduct']))
            ? $package['cotationProduct']
            : (new CartWooCommerceService())->getProducts();

        $result = (new QuotationService())->calculateQuotationByProducts(
            $products,
            $to,
            $code
        );

        if (is_array($result)) {
            $result = $this->extractOnlyQuotationByService($result, $code);
        }

        if ($result) {
            if (isset($result->price) && isset($result->name)) {
                $method = (new OptionsHelper())->getName(
                    $result->id,
                    $result->name,
                    null,
                    null
                );

                if ($this->isCorreios($code) && $this->hasMultipleVolumes($result)) {
                    return false;
                }

                return [
                    'id' => $id,
                    'label' => $method['method'] . (new TimeHelper)->setLabel(
                        $result->delivery_range,
                        $code,
                        $result->custom_delivery_range
                    ),
                    'cost' => (new MoneyHelper())->setprice($result->price, $code),
                    'calc_tax' => 'per_item',
                    'meta_data' => [
                        'delivery_time' => $result->delivery_range,
                        'company' => $company,
                        'name' => $method['method']
                    ]
                ];
            }
        }

        return false;
    }

    /**
     * Check if it has more than one volume
     *
     * @param stdClass $quotation
     * @return boolean
     */
    public function hasMultipleVolumes($quotation)
    {
        if (!isset($quotation->packages)) {
            return false;
        }

        return (count($quotation->packages) >= 2) ? true : false;
    }

    /**
     * Check if it is "Correios"
     *
     * @param int $code
     * @return boolean
     */
    public function isCorreios($code)
    {
        return in_array($code, self::SERVICES_CORREIOS);
    }

    /**
     * Function to extract the quotation by the shipping method
     *
     * @param array $quotations
     * @param int $service
     * @return object
     */
    public function extractOnlyQuotationByService($quotations, $service)
    {
        $quotationByService = array_filter(
            $quotations,
            function ($item) use ($service) {
                if ($item->id == $service) {
                    return $item;
                }
            }
        );

        if (!is_array($quotationByService)) {
            return false;
        }

        return end($quotationByService);
    }

    /**
     * Get shipping classes options.
     *
     * @return array
     */
    public function getShippingClassesOptions()
    {
        $shippingClasses = WC()->shipping->get_shipping_classes();
        $options = array(
            self::ANY_DELIVERY => 'Qualquer classe de entrega',
            self::WITHOUT_DELIVERY  => 'Sem classe de entrega',
        );

        if (!empty($shippingClasses)) {
            $options += wp_list_pluck($shippingClasses, 'name', 'term_id');
        }

        return $options;
    }

    /**
     * Check if package uses only the selected shipping class.
     *
     * @param  array $package Cart package.
     * @param int $shippingClassId
     * @return bool
     */
    public function hasOnlySelectedShippingClass($package, $shippingClassId)
    {
        $onlySelected = true;

        if (self::ANY_DELIVERY === $shippingClassId) {
            return $onlySelected;
        }

        foreach ($package['contents'] as $values) {
            $product = $values['data'];
            $qty     = $values['quantity'];

            if ($product->get_shipping_class_id() == self::WITHOUT_DELIVERY) {
                $onlySelected = true;
                break;
            }

            if ($qty > 0 && $product->needs_shipping()) {
                if ($shippingClassId !== $product->get_shipping_class_id()) {
                    $onlySelected = false;
                    break;
                }
            }
        }

        return $onlySelected;
    }
}
