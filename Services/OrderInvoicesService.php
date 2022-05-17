<?php

namespace Services;

class OrderInvoicesService
{
    const POST_META_INVOICE = 'melhorenvio_invoice_v2';

    /**
     * Function to save invoice by order.
     *
     * @param int $post_id
     * @param numeric $key
     * @param numeric $number
     * @return array
     */
    public function insertInvoiceOrder($post_id, $key, $number)
    {
        delete_post_meta($post_id, self::POST_META_INVOICE);

        $invoice = [
            'key' => $key,
            'number' => $number
        ];

        $result = add_post_meta(
            $post_id,
            self::POST_META_INVOICE,
            $invoice,
            true
        );

        if (!$result) {
            return [
                'key' => null,
                'number' => null
            ];
        }

        return $invoice;
    }

    /**
     * Function to retrieve invoice by order. 
     *
     * @param int $post_id
     * @return array $invoice
     */
    public function getInvoiceOrder($post_id)
    {
        $invoice = get_post_meta($post_id, self::POST_META_INVOICE, true);

        if (!$invoice) {
            return [
                'key' => null,
                'number' => null
            ];
        }

        return $invoice;
    }

    /**
     * Function to check order is non commercial
     *
     * @param int $post_id
     * @return boolean
     */
    public function isNonCommercial($post_id)
    {
        $invoice = get_post_meta($post_id, self::POST_META_INVOICE, true);

        return (!isset($invoice['key']) || !isset($invoice['number']));
    }
}
