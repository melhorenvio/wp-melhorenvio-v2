<?php

namespace Services;

use Models\AgencyLatam;

class AgenciesLatamService
{
    const ROUTE_GET_AGENCIES = '/shipment/agencies';

    const COMPANY_ID_LATAM = 6;

    /**
     * function to get Latam agencies on Melhor Envio API.
     *
     * @return array
     */
    public function get()
    {
        $seller = (new SellerService())->getData();

        if (empty($seller->city)) {
            return (object) [
                'success' => false,
                'message' => 'Não foi possível obter a cidade cadastrada para obter as informações das unidades Latam'
            ];
        }

        $agencies = [];
        if (!empty($seller->state_abbr)) {
            $agencies = $this->getByAddress($seller->city, $seller->state_abbr);
            if (empty($agencies->success)) {
                $agencies = $this->getByState($seller->state_abbr);
            }
        }

        if (empty($agencies)) {
            return (object) [
                'success' => false,
                'message' => sprintf(
                    "Ocorreu um erro ao obter unidades Latam para a cidade %s/%s",
                    $seller->city,
                    $seller->state_abbr
                )
            ];
        }

        return $this->markAsSelectedByUser($agencies);
    }

    /**
     * function to get agencies Latam by city
     *
     * @param string $city
     * @param string $state
     * @return object
     */
    public function getByAddress($city, $state)
    {
        $route = urldecode(sprintf(
            "%s?company=%d&country=BR&state=%s&city=%s",
            self::ROUTE_GET_AGENCIES,
            self::COMPANY_ID_LATAM,
            $state,
            $city
        ));

        $agencies = (new RequestService())->request(
            $route,
            'GET',
            [],
            false
        );

        return $this->markAsSelectedByUser($agencies);
    }

    /**
     * function to get agencies Latam by state
     *
     * @param string $state
     * @return object
     */
    public function getByState($state)
    {
        $route = urldecode(sprintf(
            "%s?company=%d&country=BR&state=%s",
            self::ROUTE_GET_AGENCIES,
            self::COMPANY_ID_LATAM,
            $state
        ));

        return (new RequestService())->request(
            $route,
            'GET',
            [],
            false
        );
    }

    /**
     * function to get agencies Latam states user
     *
     * @return array
     */
    public function getByStateUser()
    {
        $seller = (new SellerService())->getData();

        $agencies = $this->getByState($seller->state_abbr);

        return $this->markAsSelectedByUser($agencies);
    }

    /**
     * function to mark as selected agency.
     *
     * @param array $agencies
     * @return array
     */
    public function markAsSelectedByUser($agencies)
    {
        $selectedAgency = (new AgencyLatam())->getSelected();

        if (empty($selectedAgency)) {
            return $agencies;
        }

        return array_map(function ($agency) use ($selectedAgency) {
            $data = (array) $agency;
            $data['selected'] = ($data['id'] === $selectedAgency);
            return (object) $data;
        }, $agencies);
    }

    /**
     * function to return a id agency selected in configs plugin,
     * if not has selected agency, return first agency by city user.
     *
     * @return int $id
     */
    public function getSelectedAgencyOrAnyByCityUser()
    {
        $selectedAgency = (new AgencyLatam())->getSelected();

        if (!empty($selectedAgency)) {
            return $selectedAgency;
        }

        $agencies = $this->get();

        if (isset($agencies->success) && !$agencies->success) {
            return null;
        }

        $agency = end($agencies);

        return $agency->id;
    }
}
