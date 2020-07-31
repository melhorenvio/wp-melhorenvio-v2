<?php

namespace Controllers;

use Models\Address;
use Models\Agency;
use Models\Store;
use Models\CalculatorShow;
use Models\JadlogAgenciesShow;
use Models\Method;
use Services\ConfigurationsService;

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
    public function saveToken($tokenUser)
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
     * User selected function to return jadlog agency
     *
     * @return json
     */
    public function getAgencyJadlog()
    {
        return wp_send_json(
            (new Agency())->getAgencies(),
            200
        );
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

        $options = $this->getOptionsShipments();

        $enableds = (new Method())->getArrayShippingMethodsEnabledByZoneMelhorEnvio();

        $shipping_methods = \WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }
            if (in_array($method->id, $enableds)) {
                $methods[] = [
                    'code' => $method->code,
                    'title' => str_replace(' (Melhor Envio)', '', $method->method_title),
                    'name' => (isset($options[$method->code]['name']) && $options[$method->code]['name'] != "undefined" && $options[$method->code]['name'] != "") ? $options[$method->code]['name'] : str_replace(' (Melhor Envio)', '', $method->method_title),
                    'tax' => (isset($options[$method->code]['tax'])) ? floatval($options[$method->code]['tax']) : 0,
                    'time' => (isset($options[$method->code]['time'])) ? floatval($options[$method->code]['time']) : 0,
                    'perc' => (isset($options[$method->code]['perc'])) ? floatval($options[$method->code]['perc']) : 0,
                    'ar' => (isset($options[$method->code]['ar']) && $options[$method->code]['ar'] == "true"),
                    'mp' => (isset($options[$method->code]['mp']) && $options[$method->code]['mp'] == "true")
                ];
            }
        }

        return wp_send_json($methods, 200);
    }

    /**
     * @return array
     */
    public function getMethodsEnablesArray()
    {
        $methods = [];

        $options = $this->getOptionsShipments();

        $enableds =  (new Method())->getArrayShippingMethodsEnabledByZoneMelhorEnvio();

        $shipping_methods = \WC()->shipping->get_shipping_methods();

        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }

            if (in_array($method->id, $enableds)) {
                $methods[] = [
                    'code' => $method->code,
                    'title' => str_replace(' (Melhor Envio)', '', $method->method_title),
                    'name' => (isset($options[$method->code]['name']) && $options[$method->code]['name'] != "undefined" && $options[$method->code]['name'] != "") ? $options[$method->code]['name'] : str_replace(' (Melhor Envio)', '', $method->method_title),
                    'tax' => (isset($options[$method->code]['tax'])) ? floatval($options[$method->code]['tax']) : 0,
                    'time' => (isset($options[$method->code]['time'])) ? floatval($options[$method->code]['time']) : 0,
                    'perc' => (isset($options[$method->code]['perc'])) ? floatval($options[$method->code]['perc']) : 0,
                    'ar' => (isset($options[$method->code]['ar']) && $options[$method->code]['ar'] == "true")
                        ? true
                        : false,
                    'mp' => (isset($options[$method->code]['mp']) && $options[$method->code]['mp'] == "true")
                        ? true
                        : false
                ];
            }
        }

        return $methods;
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

    public function setPathPlugins($path)
    {
        delete_option('melhor_envio_path_plugins');
        add_option('melhor_envio_path_plugins', $path);

        return get_option('melhor_envio_path_plugins');
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

    public function getPathPluginsArray()
    {
        $path = get_option('melhor_envio_path_plugins');

        if (!$path) {
            $path = ABSPATH . 'wp-content/plugins';
        }

        return $path;
    }

    public function saveoptionsMethod($item)
    {
        $id = $item['id'];

        delete_option('melhor_envio_option_method_shipment_' . $id);
        add_option('melhor_envio_option_method_shipment_' . $id, $item);

        return get_option('melhor_envio_option_method_shipment_' . $id);
    }

    public function getOptionsShipments()
    {
        $codeStore = md5(get_option('home'));

        global $wpdb;
        $sql = "select * from " . $wpdb->prefix . "options where option_name like '%melhor_envio_option_method_shipment_%'";
        $results = $wpdb->get_results($sql);


        if (empty($results)) {
            return false;
        }

        $options = [];
        foreach ($results as $item) {

            if (empty($item->option_value)) {
                continue;
            }

            $data = unserialize($item->option_value);

            if (isset($data['id'])) {

                $options[$data['id']] = [
                    'name'       => $data['name'],
                    'tax'        => $data['tax'],
                    'time'       => $data['time'],
                    'perc'       => $data['perc'],
                    'mp'         => $data['mp'],
                    'ar'         => $data['ar'],
                    'code_modal' => 'code_shiping_' . $data['id']
                ];
            }
        }

        $_SESSION[$codeStore]['melhorenvio_options'] = $options;

        return $options;
    }


    public function setWhereCalculator($option)
    {

        delete_option('melhor_envio_option_where_show_calculator');
        add_option('melhor_envio_option_where_show_calculator', $option);

        return [
            'success' => true,
            'option' => $option
        ];
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
        delete_option('melhorenvio_ar');
        delete_option('melhorenvio_mp');

        add_option('melhorenvio_ar', $options['ar'], true);
        add_option('melhorenvio_mp', $options['mp'], true);

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
            'ar' => filter_var(get_option('melhorenvio_ar', "false"), FILTER_VALIDATE_BOOLEAN),
            'mp' => filter_var(get_option('melhorenvio_mp', "false"), FILTER_VALIDATE_BOOLEAN)
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
        $data = $_POST;

        $response = [];

        if (isset($data['address'])) {
            $response['address'] = (new Address())->setAddressShopping($data['address']);
        }

        if (isset($data['store'])) {
            $response['store'] = (new Store())->setStore($data['store']);
        }

        if (isset($data['agency'])) {
            $response['agency'] = (new Agency())->setAgency($data['agency']);
        }

        if (isset($data['show_calculator'])) {
            $response['show_calculator'] = (new CalculatorShow())->set($data['show_calculator']);
        }

        if (isset($data['show_all_agencies_jadlog'])) {
            $response['show_all_agencies_jadlog'] = (new JadlogAgenciesShow())->set($data['show_all_agencies_jadlog']);
        }

        if (isset($data['methods_shipments'])) {
            foreach ($data['methods_shipments'] as $key => $method) {
                $response['method'][$key] = $this->saveoptionsMethod($method);
            }
        }

        if (isset($data['where_calculator'])) {
            $response['where_calculator'] = $this->setWhereCalculator($data['where_calculator']);
        }

        if (isset($data['path_plugins'])) {
            $response['path_plugins'] = $this->setPathPlugins($data['path_plugins']);
        }

        if (isset($data['options_calculator'])) {
            $response['options_calculator'] = $this->setOptionsCalculator($data['options_calculator']);
        }
        return wp_send_json($response, 200);
    }
}
