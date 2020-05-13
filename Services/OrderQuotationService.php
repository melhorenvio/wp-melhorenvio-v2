<?php

namespace Services;

use Models\Method;

class OrderQuotationService
{
    const POST_META_ORDER_QUOTATION = 'melhorenvio_quotation_v2';

    const POST_META_ORDER_DATA = 'melhorenvio_status_v2';

    const OPTION_TOKEN_ENVIRONMENT = 'wpmelhorenvio_token_environment';

    const CORREIOS_MINI_CODE = 17;

    protected $env;

    public function __construct()
    {
        $this->env = $this->getEnvironmentToSave();
    }

    /**
     * Function to get a quotation by order in postmetas by wordpress.
     *
     * @param integer $order_id
     * @return object $quotation
     */
    public function getQuotation($order_id)
    {
        $quotation = get_post_meta($order_id, self::POST_META_ORDER_QUOTATION, true);

        if (!$quotation || $this->isUltrapassedQuotation($quotation)) {  

            $quotation = (new QuotationService())->calculateQuotationByOrderId($order_id);
        
            $quotation = $this->saveQuotation($order_id, $quotation);
        }

        return $quotation;
    }

    /**
     * Save quotation in postmeta wordpress.
     *
     * @param int $order_id
     * @param object $quotation
     * @return array $quotation
     */
    public function saveQuotation($order_id, $quotation)
    {
        $result = $this->setKeyAsCodeService($quotation);
        $result['date_quotation'] = date('Y-m-d H:i:d'); 
        $result['choose_method'] = (new Method($order_id))->getMethodShipmentSelected($order_id); //TODO
        $result['free_shipping'] = false; 
        $result['diff'] = false;

        delete_post_meta($order_id, self::POST_META_ORDER_QUOTATION);
        add_post_meta($order_id, self::POST_META_ORDER_QUOTATION, $result, true);

        return $result;
    }

    /**
     * Set a key of quotations array as code service.
     *
     * @param array $quotation
     * @return array $quotationoid
     */
    private function setKeyAsCodeService($quotation)
    {
        $result = [];
        
        foreach ($quotation as $item) {
            
            $result[$item->id] = $item;

            if ($item->id == self::CORREIOS_MINI_CODE) {
                foreach ($item->packages as $key => $package) {
                    if ($package->weight == 0) {
                        $result[$item->id]['packages'][$key]->weight = 0.01;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get postmeta data by order (Status, order_id, protocol).
     *
     * @param int $order_id
     * @return array $data
     */
    public function getData($order_id)
    {   
        return get_post_meta($order_id, self::POST_META_ORDER_DATA . $this->env, true);
    }

    /**
     * Function to update data quotation by order.
     * 
     * @param int $order_id
     * @param string $order_melhor_envio_id
     * @param string $protocol
     * @param string $status
     * @param int $choose_method
     * @return array $data
     */
    public function updateDataQuotation($order_id, $order_melhor_envio_id, $protocol, $status, $choose_method, $purcahse_id = null) 
    {
        $data = [
            'choose_method' => $choose_method,
            'order_id' => $order_melhor_envio_id,
            'protocol' => $protocol,
            'purchase_id' => $purcahse_id,
            'status' => $status,
            'created' => date('Y-m-d H:i:s')
        ];

        delete_post_meta($order_id, self::POST_META_ORDER_DATA . $this->env);
        add_post_meta($order_id, self::POST_META_ORDER_DATA . $this->env, $data, true);

        return $data;
    }

    /** 
     * @param int $order_id
     */
    public function removeDataQuotation($order_id)
    {
        delete_post_meta($order_id, self::POST_META_ORDER_DATA . $this->env);
    }

    /**
     * Function to check if a quotation is ultrapassed.
     *
     * @param array $data
     * @return boolean
     */
    public function isUltrapassedQuotation($data)
    {
        if (!isset($data['date_quotation'])) { return true; }

        $date = date('Y-m-d H:i:s', strtotime("-3 day"));

        ($date_quotation > $data['date_quotation']) ? false : true;
    }

    public function getEnvironmentToSave()
    {
        $environment = get_option(self::OPTION_TOKEN_ENVIRONMENT);
        $env = null;
        if ($environment == 'sandbox') {
            $env = '_sandbox';
        }

        return $env;
    }
}