<?php

namespace Models;

use Models\Address;
use Services\RequestService;

class Agency
{
    const AGENCY_SELECTED = 'melhorenvio_agency_jadlog_v2';

    /**
     * @return void
     */
    public function get()
    {
        $results = '';

        if (!isset($_SESSION['melhor_envio']['agencies']) || empty($_SESSION['melhor_envio']['agencies'])) {

            if (!isset($_GET['state']) && !isset($_GET['state'])) {
                $address = (new Address)->getAddressFrom();
            } else {
                $address['address'] = array(
                    'city'  => $_GET['city'],
                    'state' => $_GET['state']
                );
            }

            $results = (new RequestService())->request(
                '/shipment/agencies?company=2&country=BR&state=' . $address['address']['state'],
                'GET',
                [],
                false
            );

            $_SESSION['melhor_envio']['agencies'] = $results;
        } else {
            $results = $_SESSION['melhor_envio']['agencies'];
        }

        $agencies = [];
        $agenciesForUser = [];

        $agencySelected = get_option(self::AGENCY_SELECTED);

        foreach ($results as $agency) {
            if ($address['address']['state'] === $agency->address->city->state->state_abbr && $address['address']['city'] === $agency->address->city->city) {
                $agenciesForUser[] = array(
                    'id'           => $agency->id,
                    'name'         => $agency->name,
                    'company_name' => $agency->company_name,
                    'selected'     => ($agency->id == intval($agencySelected)) ? true : false,
                    'address'      => array(
                        'address'  => $agency->address->address,
                        'city'     => $agency->address->city->city,
                        'state'    => $agency->address->city->state->state_abbr
                    )
                );
            }

            $agencies[] = array(
                'id'           => $agency->id,
                'name'         => $agency->name,
                'company_name' => $agency->company_name,
                'selected'     => ($agency->id == intval($agencySelected)) ? true : false,
                'address'      => array(
                    'address'  => $agency->address->address,
                    'city'     => $agency->address->city->city,
                    'state'    => $agency->address->city->state->state_abbr
                )
            );
        }

        return array(
            'success'  => true,
            'origin'   => 'api',
            'agencies' => $agenciesForUser,
            'allAgencies' => $agencies,
            'agencySelected' => $agencySelected
        );
    }

    /**
     * @param string $id
     * @return bool
     */
    public function setAgency($id)
    {
        delete_option(self::AGENCY_SELECTED);
        if (!add_option(self::AGENCY_SELECTED, $id)) {
            return false;
        }

        return true;
    }

    /**
     * Return a code agency selected in configs plugin 
     *
     * @return int $code
     */
    public function getCodeAgencySelected()
    {
        $agencies = $this->get();

        foreach ($agencies['allAgencies'] as $agency) {
            if ($agency['selected']) {
                return $agency['id'];
            }
        }

        if (isset($agencies['agencies'])) {
            return end($agencies['agencies'])[0]['id'];
        }

        return null;
    }
}
