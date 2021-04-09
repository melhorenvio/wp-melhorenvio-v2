<?php

namespace Services;

use Helpers\SessionHelper;

/**
 * Class AdditionalQuotationService
 * @package Services
 */
class AdditionalQuotationService
{
    /**
     * constant where information about fees, time and extra percentage
     * of shipping methods will be saved in the session
     */
    const SESSION_KEY_ADDITIONAL = 'melhorenvio_additional';

    /**
     * Function to scan the rate, time and extra percentage data
     * that is stored in the session to return the highest values.
     *
     * @return bool|array
     */
    public function get()
    {
        SessionHelper::initIfNotExists();

        global $woocommerce;

        if (empty($woocommerce->cart->cart_contents)) {
            $_SESSION[self::SESSION_KEY_ADDITIONAL] = null;
            return false;
        }

        $maxTax = 0;
        $maxTime = 0;
        $maxPercent = 0;
        $responseFees = [];

        foreach($woocommerce->cart->get_cart() as $cart) {

            $hashCart = $cart['key'];

            if (!empty($_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart])) {

                foreach ($_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart] as $productId => $instances) {
                    foreach ($instances as $instanceId => $data) {
                        $tax =  (!empty($data['taxExtra'])) ? floatval($data['taxExtra']) : 0;
                        $time = (!empty($data['timeExtra'])) ? floatval($data['timeExtra']) : 0;
                        $percent = (!empty($data['percentExtra'])) ? floatval($data['percentExtra']) : 0;

                        $maxTax = ($tax > $maxTax) ? $tax : $maxTax;
                        $maxTime = ($time > $maxTime) ? $time : $maxTime;
                        $maxPercent = ($percent > $maxPercent) ? $percent : $maxPercent;

                        $responseFees[$instanceId] = [
                            'taxExtra' => $maxTax,
                            'timeExtra' => $maxTime,
                            'percentExtra' => $maxPercent
                        ];
                    }
                }
            }
        }
        
        return $responseFees;
    }

    /**
     * Function to save the fees, time and extra percentage of the product in the session
     * when it is inserted in the shopping cart.
     *
     * @param int $product_id
     * @param float $instanceId
     * @param float $taxExtra
     * @param int $timeExtra
     * @param float $percent
     * @return bool
     */
    public function register($product_id, $instanceId, $taxExtra, $timeExtra, $percent)
    {
        SessionHelper::initIfNotExists();

        global $woocommerce;

        if (empty($woocommerce->cart->get_cart_contents_count() )) {
            $_SESSION[self::SESSION_KEY_ADDITIONAL] = null;
            return false;
        }

        foreach($woocommerce->cart->get_cart() as $cart) {
            $hashCart = $cart['key'];

            if (empty($_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart])) {
                $_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart][$product_id][$instanceId] = [
                    'taxExtra' => $taxExtra,
                    'timeExtra' => $timeExtra,
                    'percent' => $percent
                ];

                session_write_close();
                return true;
            }

            $dataCachedAdditional = $_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart];

            $dataCachedAdditional = array_merge(
                $dataCachedAdditional, [
                    'taxExtra' => 0,
                    'timeExtra' => 0,
                    'percent' => 0
                ]
            );

            $_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart][$product_id][$instanceId] = [
                'taxExtra' => ($taxExtra > $dataCachedAdditional['taxExtra'])
                    ? $taxExtra
                    : $dataCachedAdditional['taxExtra'],
                'timeExtra' => ($timeExtra > $dataCachedAdditional['timeExtra'])
                    ? $timeExtra
                    : $dataCachedAdditional['timeExtra'],
                'percent' => ($percent > $dataCachedAdditional['percent'])
                    ? $percent
                    : $dataCachedAdditional['percent']
            ];
        }

        session_write_close();
        return true;
    }

    /**
     * Function to remove the rate, time and extra percentage data from the product
     * that is removed from the shopping cart.
     *
     * @param $productId
     */
    public function removeItem($productId)
    {
        SessionHelper::initIfNotExists();

        if (empty($_SESSION[self::SESSION_KEY_ADDITIONAL] )) {
            return false;
        }

        foreach ($_SESSION[self::SESSION_KEY_ADDITIONAL] as $key => $cart) {
            foreach ($cart as $key2 => $item) {
                if ($key2 == $productId) {
                    unset($_SESSION[self::SESSION_KEY_ADDITIONAL][$key][$key2]);
                }
            }
        }

        session_write_close();
    }
}
