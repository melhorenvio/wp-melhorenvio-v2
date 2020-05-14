<?php

namespace Services;

class InvoiceService
{
    const POST_META_INVOICE = 'melhorenvio_invoice_v2';

    /**
     * @param int $post_id
     * @return array $invoice
     */
    public function getInvoice($post_id) 
    {
        $invoice = get_post_meta($post_id, self::POST_META_INVOICE);

        if(count($invoice) > 0) {
            return end($invoice);
        } 
        
        return [
            'number' => null, 
            'key' => null 
        ];
    }
}