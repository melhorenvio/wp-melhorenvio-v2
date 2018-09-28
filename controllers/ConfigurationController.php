<?php

namespace Controllers;
use Models\Address;
use Models\Agency;

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
}

