<?php

namespace Services;

/**
 * Location service class
 */
class LocationService
{
    /**
     * Melhor Envio location api URL
     */
    protected const URL = "https://location.melhorenvio.com.br/";

    /**
     * Via CEP location api URL
     */
    protected const URL_VIA_CEP = "https://viacep.com.br/ws/";

    /**
     * Function to search for address in zip code api
     *
     * @param string $postalCode
     * @return object
     */
    public function getAddressByPostalCode($postalCode)
    {
        $postalCode = str_replace("-", "", $postalCode);
        $postalCode = floatval($postalCode);

        if (empty($postalCode)) return null;

        $address = $this->getAddressByPostalCodeLocationMelhorEnvio($postalCode);

        if (!$address) {
            $address = $this->getAddressByPostalCodeLocationViaCep($postalCode);
        }

        if ($address->erro) {
            return null;
        }

        return $address;
    }

    /**
     * Function to search for address in zip code api Melhor Envio
     *
     * @param float $postalCode
     * @return object
     */
    public function getAddressByPostalCodeLocationMelhorEnvio($postalCode)
    {
        $postalCode = str_pad($postalCode, 8, '0', STR_PAD_LEFT);

        $url = self::URL . $postalCode;

        $result = json_decode(
            wp_remote_retrieve_body(
                wp_remote_get($url)
            )
        );

        if (isset($result->message)) {
            return false;
        }

        return $result;
    }

    /**
     * Function to search for address in zip code api Via Cep
     *
     * @param float $postalCode
     * @return object
     */
    public function getAddressByPostalCodeLocationViaCep($postalCode)
    {
        $postalCode = str_pad($postalCode, 8, '0', STR_PAD_LEFT);

        $url = self::URL_VIA_CEP . $postalCode . '/json';

        $result = json_decode(
            wp_remote_retrieve_body(
                wp_remote_get($url)
            )
        );

        if (!$result) {
            return false;
        }

        return $result;
    }
}
