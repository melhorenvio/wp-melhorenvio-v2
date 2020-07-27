<?php 

namespace Services;

use Helpers\MoneyHelper;
use Helpers\OptionsHelper;
use Helpers\TimeHelper;

class CalculateShippingMethodService
{
    const SERVICES_CORREIOS = ['1', '2', '17'];

    public function calculate_shipping( $package = [], $code, $id, $company)
    {
        $to = preg_replace('/\D/', '', $package['destination']['postcode']);

        $products = (isset($package['cotationProduct'])) ? $package['cotationProduct'] : (new CartWooCommerceService())->getProducts();

        $result = (new QuotationService())->calculateQuotationByProducts($products, $to, $code);

        if ($result) {

            if (isset($result->name) && isset($result->price)) {

                $method = (new OptionsHelper())->getName($result->id, $result->name, null, null);

                if ($this->isCorreiosAndHasVolumes($code, $result)) {
                    return false;
                }

                return [
                    'id' => $id,
                    'label' => $method['method'] . (new TimeHelper)->setLabel($result->delivery_range, $code, $result->custom_delivery_range),
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
     * Check if it is "Correios" and if it has more than one volume
     *
     * @param int $code
     * @param stdClass $quotation
     * @return boolean
     */
    public function isCorreiosAndHasVolumes($code, $quotation)
    {
        if (!isset($quotation->packages)) {
            return false;
        }

        return ( in_array($code, self::SERVICES_CORREIOS) && count($quotation->packages) >= 2 ) ? true : false;
    }
}