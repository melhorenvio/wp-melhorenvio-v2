<?php

namespace Controllers;
use Models\Address;
use Models\Agency;
use Models\Store;
use Models\CalculatorShow;

class ConfigurationController {

    public function saveToken($tokenUser) {

        $token = get_option('melhorenvio_token');
        if (!$token or empty($token)) {
            add_option('melhorenvio_token', $tokenUser);
        }

        update_option('melhorenvio_token', $tokenUser,true);
        return get_option('melhorenvio_token');
    }

    public function getAddressShopping() {
        $address = new Address();
        echo json_encode($address->getAddressesShopping());
        die;
    }

    public function setAddressShopping() {
        $address = new Address();
        echo json_encode($address->setAddressShopping($_GET['id']));
        die;
    }

    public function setAgencyJadlog() {
        $agency = new Agency();
        echo json_encode($agency->setAgency($_GET['id']));
        die;
    }

    public function getAgencyJadlog() {
        $agency = new Agency();
        echo json_encode($agency->getAgencies());
        die;
    }

    public function getStories() {
        $story = new Store();
        echo json_encode($story->getStories());
        die;
    }

    public function setStore() {
        $story = new Store();
        echo json_encode($story->setStore($_GET['id']));
        die;
    }

    public function get_calculator_show() {
        $calc = new CalculatorShow();
        echo json_encode($calc->get());
        die;
    }

    public function set_calculator_show() {
        $calc = new CalculatorShow();
        echo json_encode($calc->set($_GET['data']));
        die;
    }
}

