<?php

namespace Services;

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
     * @return false|array
     */
    public function get()
    {
        session_start();

        global $woocommerce;

        if (empty($woocommerce->cart->cart_contents)) {
            $_SESSION[self::SESSION_KEY_ADDITIONAL] = null;
            return false;
        }

        $maxTax = 0;
        $maxTime = 0;
        $maxPercent = 0;

        foreach ($woocommerce->cart->get_cart() as $cart) {

            $hashCart = $cart['key'];

            if (!empty($_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart])) {
                foreach ($_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart] as $data) {
                    $tax = floatval($data['taxExtra']);
                    $time = floatval($data['timeExtra']);
                    $percent = floatval($data['percentExtra']);

                    $maxTax = ($tax > $maxTax) ? $tax : $maxTax;
                    $maxTime = ($time > $maxTime) ? $time : $maxTime;
                    $maxPercent = ($percent > $maxPercent) ? $percent : $maxPercent;
                }
            }
        }

        return [
            'taxExtra' => $maxTax,
            'timeExtra' => $maxTime,
            'percentExtra' => $maxPercent
        ];
    }

    /**
     * Function to save the fees, time and extra percentage of the product in the session
     * when it is inserted in the shopping cart.
     *
     * @param $product_id
     * @param $taxExtra
     * @param $timeExtra
     * @param $percent
     * @return bool
     */
    public function register($product_id, $taxExtra, $timeExtra, $percent)
    {
        session_start();

        global $woocommerce;

        if ($woocommerce->cart->get_cart_contents_count() == 0) {
            $_SESSION[self::SESSION_KEY_ADDITIONAL] = null;
            return false;
        }

        foreach ($woocommerce->cart->get_cart() as $cart) {
            $hashCart = $cart['key'];

            if (empty($_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart])) {
                $_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart][$product_id] = [
                    'taxExtra' => $taxExtra,
                    'timeExtra' => $timeExtra,
                    'percent' => $percent
                ];

                session_write_close();
                return true;
            }

            $dataCachedAdditional = $_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart];

            $_SESSION[self::SESSION_KEY_ADDITIONAL][$hashCart][$product_id] = [
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
        session_start();

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
