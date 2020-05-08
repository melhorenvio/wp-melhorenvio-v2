<?php

namespace Services;

class SellerService
{
    /**
     * Get data user on API Melhor Envio
     *
     * @return array $dataSeller
     */
    public function getData()
    {
        $dataMelhorEnvio = $this->getDataApiMelhorEnvio();
        $data = $dataMelhorEnvio;
        return (object) [
            "name" => sprintf("%s %s", $data->firstname, $data->lastname),
            "phone" => (isset($data->phone->phone)) ? $data->phone->phone : null,
            "email" => $data->email,
            "document" => $data->document,
            //"company_document" => null,
            //"state_register" => null,
            "address" => $data->address->address,
            "complement" => $data->address->complement,
            "number" => $data->address->number,
            "district" => $data->address->district,
            "city" => $data->address->city->city,
            "state_abbr" => $data->address->city->state->state_abbr,
            "country_id" => 'BR',
            "postal_code" => $data->address->postal_code
        ]; 
    }

    /**
     * Get data user on API Melhor Envio
     *
     * @return array $data
     */
    private function getDataApiMelhorEnvio()
    {
        $data = (new RequestService())->request('', 'GET', []);

        if (!isset($data->id)) {
            return [
                'success' => false,
                'message' => 'UsuÃ¡rio nÃ£o encontrado no Melhor Envio'
            ];
        }

        return $data;
    }
}
