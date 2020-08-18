<?php

namespace Models;

use Models\Address;
use Services\RequestService;
use Services\SellerService;

class Agency
{
    const AGENCY_SELECTED = 'melhorenvio_agency_jadlog_v2';

    /**
     * @return void
     */
    public function get()
    {

        $seller = (new SellerService())->getData();

        $state = (!empty($_GET['state'])) ? $_GET['state'] : $seller->state_abbr;

        $results = (new RequestService())->request(
            '/shipment/agencies?company=2&country=BR&state=' . $state,
            'GET',
            [],
            false
        );

        $agencies = [];

        $agenciesForUser = [];

        $agencySelected = get_option(self::AGENCY_SELECTED);

        foreach ($results as $agency) {

            if ($state === $agency->address->city->state->state_abbr) {
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

        if (!empty($agencies['agencySelected'])) {
            return $agencies['agencySelected'];
        }
        

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
