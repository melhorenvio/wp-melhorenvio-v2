<?php

namespace Models;

use Services\OrderQuotationService;

class Order
{
    // Não confundir esses status com os status utlizados no Melhor Envio.

    const STATUS_GENERATED = 'generated';

    const STATUS_RELEASED = 'released';

    const STATUS_PAID = 'paid';

    const STATUS_PENDING = 'pending';

    private $id;

    /**
     * @param int $id
     */
    public function __construct($id = null)
    {
        try {

            $orderWc = new \WC_Order($id);

            $data = $orderWc->get_data();

            $this->id = $id;

            $this->address = $data['shipping'];

            $this->products = $this->getProducts();

            $this->total = 0;

            $this->shipping_total = 0;

            $this->to = $data['billing'];

            $this->cotation = (array) $this->getCotation();
        } catch (\Exception $e) {
        }
    }

    /**
     * Retrieve all products in Order.
     *
     * @param int $id
     * @return object
     */
    protected function getProducts()
    {
        $orderWc = new \WC_Order($this->id);
        $order_items = $orderWc->get_items();
        $products = [];
        foreach ($order_items as $product) {
            $data = $product->get_data();
            $products[] = (object) [
                'id' => $data['product_id'],
                'variation_id' => $data['variation_id'],
                'name' => $data['name'],
                'quantity' => $data['quantity'],
                'total' => $data['total']
            ];
        }
        return $products;
    }

    /**
     * Retrieve cotation.
     *
     * @param int $id
     * @return object
     */
    public function getCotation($id = null)
    {
        if ($id) $this->id = $id;

        return (new OrderQuotationService())->getQuotation($this->id);
    }
}
