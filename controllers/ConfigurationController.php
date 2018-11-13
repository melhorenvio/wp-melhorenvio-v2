<?php

namespace Controllers;
use Models\Address;
use Models\Agency;
use Models\Store;
use Models\CalculatorShow;
use Controllers\ContationControllers;

class ConfigurationController 
{
    /**
     * @param [type] $tokenUser
     * @return void
     */
    public function saveToken($tokenUser) 
    {
        $token = get_option('melhorenvio_token');
        if (!$token or empty($token)) {
            add_option('melhorenvio_token', $tokenUser);
        }

        update_option('melhorenvio_token', $tokenUser,true);
        return get_option('melhorenvio_token');
    }

    /**
     * @return void
     */
    public function getAddressShopping() 
    {
        echo json_encode((new Address())->getAddressesShopping());
        die;
    }

    /**
     * @return void
     */
    public function setAddressShopping() 
    {
        if (!isset($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'É necessário infomar o ID do endereço'
            ]);
            die;
        }

        echo json_encode((new Address())->setAddressShopping($_GET['id']));
        die;
    }

    /**
     * @return void
     */
    public function setAgencyJadlog() 
    {
        if (!isset($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'É necessário infomar o ID da agência'
            ]);
            die;
        }

        echo json_encode((new Agency())->setAgency($_GET['id']));
        die;
    }

    /**
     * @return void
     */
    public function getAgencyJadlog() 
    {
        echo json_encode((new Agency())->getAgencies());
        die;
    }

    /**
     * @return void
     */
    public function getStories() 
    {
        echo json_encode((new Store())->getStories());
        die;
    }

    /**
     * @return void
     */
    public function setStore() 
    {
        if (!isset($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'É necessário infomar o ID da loja'
            ]);
            die;
        }

        echo json_encode((new Store())->setStore($_GET['id']));
        die;
    }

    /**
     * @return void
     */
    public function get_calculator_show() 
    {
        echo json_encode((new CalculatorShow())->get());
        die;
    }

    /**
     * @return void
     */
    public function set_calculator_show() 
    {
        if (!isset($_GET['data'])) {
            echo json_encode([
                'success' => false,
                'message' => 'É necessário infomar o parametro data ("true" ou "false")'
            ]);
            die;
        }

        echo json_encode((new CalculatorShow())->set($_GET['data']));
        die;
    }

    /**
     * @return void
     */
    public function getMethodsEnables()
    {   
        $methods = [];

        $options = $this->getOptionsShipments();

        $enableds = (new CotationController())->getArrayShippingMethodsEnabledByZoneMelhorEnvio();

        $shipping_methods = \WC()->shipping->get_shipping_methods();
        foreach ($shipping_methods as $method) {
            if (!isset($method->code) || is_null($method->code)) {
                continue;
            }
            if (in_array($method->id, $enableds)) {
                $methods[] = [
                    'code' => $method->code,
                    'title' => str_replace(' (Melhor envio)', '', $method->method_title),
                    'tax' => (isset($options[$method->code]['tax'])) ? floatval($options[$method->code]['tax']) : 0 ,
                    'time' => (isset($options[$method->code]['time'])) ? floatval($options[$method->code]['time']) : 0 
                ];
            }
        }

        echo json_encode($methods);die;
    }

    /**
     * @return void
     */
    public function save() 
    {
        $id = $_GET['id'];
        delete_option('melhor_envio_option_method_shipment_' . $id);
        unset($_GET['action']);
        add_option('melhor_envio_option_method_shipment_' . $id, $_GET);
        echo json_encode([
            'id' => $id,
            'tax' => $_GET['tax'],
            'time' => $_GET['time']
        ]);die;
    }

    public function getOptionsShipments()
    {
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
            $options[$data['id']] = [
                'tax' => $data['tax'],
                'time' => $data['time']
            ];
        }

        return $options;
    }
}
