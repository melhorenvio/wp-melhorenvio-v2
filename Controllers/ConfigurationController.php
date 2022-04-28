<?php

namespace Controllers;

use Models\Address;
use Models\Agency;
use Models\Store;
use Models\Method;
use Models\Option;
use Services\ConfigurationsService;
use Services\OptionsMethodShippingService;

/**
 * Class responsible for the configuration controller
 */
class ConfigurationController
{
    /**
     * Function to get configurations of user
     *
     * @return json
     */
    public function getConfigurations()
    {
        return wp_send_json(
            (new ConfigurationsService())->getConfigurations(),
            200
        );
    }

    /**
     * Function to save token Melhor Envio and retur the token.
     *
     * @param string $tokenUser
     * @return mixed
     */
    public function save($tokenUser)
    {
        $token = get_option('melhorenvio_token');
        if (!$token) {
            add_option('melhorenvio_token', $tokenUser);
        }

        update_option('melhorenvio_token', $tokenUser, true);
        return get_option('melhorenvio_token');
    }

    /**
     * Function to search the user's saved address
     *
     * @return json
     */
    public function getAddressShopping()
    {
        return wp_send_json(
            (new Address())->getAddressesShopping(),
            200
        );
    }

    /**
     * Function to set selected agency jadlog
     *
     * @return json
     */
    public function setAgencyJadlog()
    {
        if (!isset($_GET['id'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'É necessário infomar o ID da agência'
            ], 412);
        }


        if (!(new Agency())->setAgency($_GET['id'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'Ocorreu um erro ao atualizar a agência selecionada'
            ], 400);
        }

        return wp_send_json([
            'success' => true,
            'message' => 'Agência selecionada atualizada com successo.'
        ], 200);
    }

    /**
     * Function to search user stores
     *
     * @return json
     */
    public function getStores()
    {
        return wp_send_json(
            (new Store())->getStores(),
            200
        );
    }

    /**
     * Function to set user stores.
     *
     * @return json
     */
    public function setStore()
    {
        if (!isset($_GET['id'])) {
            return wp_send_json([
                'success' => false,
                'message' => 'É necessário infomar o ID da loja'
            ], 412);
        }

        return wp_send_json(
            (new Store())->setStore($_GET['id']),
            200
        );
    }

    /**
     * Function to return woocommerce shipping methods with name, fee and extra time settings
     *
     * @return json
     */
    public function getMethodsEnables()
    {
        $methods = [];

        $options = (new OptionsMethodShippingService())->getOptionsShipments();

        $enableds = (new Method())->getArrayShippingMethodsEnabledByZoneMelhorEnvio();

        $shipping_methods = \WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }
            if (in_array($method->id, $enableds)) {
                $methods[] = $this->filterMethod($method, $options);
            }
        }

        return wp_send_json($methods, 200);
    }

    public function filterMethod($method, $options)
    {
        return [
            'code' => $method->code,
            'title' => str_replace(' (Melhor Envio)', '', $method->method_title),
            'name' => (isset($options[$method->code]['name']) && $options[$method->code]['name'] != "undefined" && $options[$method->code]['name'] != "") ? $options[$method->code]['name'] : str_replace(' (Melhor Envio)', '', $method->method_title),
            'tax' => (isset($options[$method->code]['tax'])) ? floatval($options[$method->code]['tax']) : 0,
            'time' => (isset($options[$method->code]['time'])) ? floatval($options[$method->code]['time']) : 0,
            'perc' => (isset($options[$method->code]['perc'])) ? floatval($options[$method->code]['perc']) : 0,
            'receipt' => (isset($options[$method->code]['receipt']) && $options[$method->code]['receipt'] == "true"),
            'own_hand' => (isset($options[$method->code]['own_hand']) && $options[$method->code]['own_hand'] == "true"),
            'insurance_value' => (isset($options[$method->code]['insurance_value']) && $options[$method->code]['insurance_value'] == "true")
        ];
    }

    public function savePathPlugins()
    {
        if (empty($_GET['path'])) {
            delete_option('melhor_envio_path_plugins');
            die;
        }

        delete_option('melhor_envio_path_plugins');
        add_option('melhor_envio_path_plugins', $_GET['path']);
    }

    public function getPathPlugins()
    {
        $path = get_option('melhor_envio_path_plugins');

        if (!$path) {
            $path = ABSPATH . 'wp-content/plugins';
        }

        return wp_send_json([
            'path' => $path
        ], 200);
    }

    public function getWhereCalculator()
    {
        $option = get_option('melhor_envio_option_where_show_calculator');

        if (!$option) {
            return wp_send_json([
                'option' => 'woocommerce_before_add_to_cart_button'
            ], 200);
        }

        return wp_send_json([
            'option' => $option
        ], 200);
    }

    /**
     * Function to obtain which hook the calculator will 
     * be displayed on the product screen
     *
     * @return string
     */
    public function getWhereCalculatorValue()
    {
        $option = get_option('melhor_envio_option_where_show_calculator');
        if (!$option) {
            return 'woocommerce_before_add_to_cart_button';
        }
        return $option;
    }

    /**
     * Function to save receipt and own hands options
     *
     * @param array $options
     * @return array
     */
    public function setOptionsCalculator($options)
    {
        delete_option(Option::OPTION_RECEIPT);
        delete_option(Option::OPTION_OWN_HAND);
        delete_option(Option::OPTION_INSURANCE_VALUE);

        add_option(Option::OPTION_RECEIPT, $options['receipt'], true);
        add_option(Option::OPTION_OWN_HAND, $options['own_hand'], true);
        add_option(Option::OPTION_INSURANCE_VALUE, $options['insurance_value'], true);

        return [
            'success' => true,
            'options' => $this->getOptionsCalculator()
        ];
    }

    /**
     * Function for obtaining acknowledgment options and own hands
     *
     * @return array
     */
    public function getOptionsCalculator()
    {
        return [
            'receipt' => filter_var(get_option(Option::OPTION_RECEIPT, "false"), FILTER_VALIDATE_BOOLEAN),
            'own_hand' => filter_var(get_option(Option::OPTION_OWN_HAND, "false"), FILTER_VALIDATE_BOOLEAN),
            'insurance_value' => filter_var(get_option(Option::OPTION_INSURANCE_VALUE, "true"), FILTER_VALIDATE_BOOLEAN)
        ];
    }

    /**
     * Function to save all configs
     *
     * @param Array $data
     * @return json
     */
    public function saveAll()
    {
        $response = (new ConfigurationsService())->saveConfigurations($_POST);

        return wp_send_json($response, 200);
    }
}
