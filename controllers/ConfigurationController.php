<?php

namespace Controllers;
use Models\Address;
use Models\Agency;
use Models\Store;
use Models\CalculatorShow;

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
}
