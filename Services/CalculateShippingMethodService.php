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
    protected const ANY_DELIVERY = -1;

    /**
     * Constant for no delivery class
     */

    protected const WITHOUT_DELIVERY = 0;

    const SERVICES_CORREIOS = ['1', '2', '17'];

    /**
     * Function to carry out the freight quote in the Melhor Envio api.
     *
     * @param array $package
     * @param int $contde
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

        if ($result) {
            if (isset($result->name) && isset($result->price)) {
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
     * Get shipping classes options.
     *
     * @return array
     */
    public function getShippingClassesOptions()
    {
        $shipping_classes = WC()->shipping->get_shipping_classes();
        $options = array(
            self::ANY_DELIVERY => 'Qualquer classe de entrega',
            self::WITHOUT_DELIVERY  => 'Sem classe de entrega',
        );

        if (!empty($shipping_classes)) {
            $options += wp_list_pluck($shipping_classes, 'name', 'term_id');
        }

        return $options;
    }

    /**
     * Check if package uses only the selected shipping class.
     *
     * @param  array $package Cart package.
     * @param int $shipping_class_id
     * @return bool
     */
    public function hasOnlySelectedShippingClass($package, $shipping_class_id)
    {
        $only_selected = true;

        if (-1 === $shipping_class_id) {
            return $only_selected;
        }

        foreach ($package['contents'] as $values) {
            $product = $values['data'];
            $qty     = $values['quantity'];

            if ($product->get_shipping_class_id() == 0) {
                $only_selected = true;
                break;
            }

            if ($qty > 0 && $product->needs_shipping()) {
                if ($shipping_class_id !== $product->get_shipping_class_id()) {
                    $only_selected = false;
                    break;
                }
            }
        }

        return $only_selected;
    }
}
