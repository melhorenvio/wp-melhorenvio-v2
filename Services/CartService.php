<?php

namespace Services;

use Models\Agency;

class CartService
{
    const PLATAFORM = 'WooCommerce V2';

    const ROUTE_MELHOR_ENVIO_ADD_CART = '/cart';

    /**
     * Function to add item on Cart Melhor Envio
     *
     * @param int $orderId
     * @param array $products
     * @param array $to
     * @param integer $shippingMethodId
     * @return void
     */
    public function add($orderId, $products, $to, $shippingMethodId)
    {
        $from = (new SellerService())->getData();

        $quotation = (new QuotationService())->calculateQuotationByOrderId($orderId);

        $orderInvoiceService = new OrderInvoicesService();

        $body = array(
            'from' => $from,
            'to' => $to,
            'agency' => (new Agency())->getCodeAgencySelected(),
            'service' => $shippingMethodId,
            'products' => $products,
            'volumes' => $this->getVolumes($quotation, $shippingMethodId),
            'options' => array(
                "insurance_value" => $this->getInsuranceValueByProducts($products),
                "receipt" => (get_option('melhorenvio_ar') == 'true') ? true : false,
                "own_hand" => (get_option('melhorenvio_mp') == 'true') ? true : false,
                "collect" => false,
                "reverse" => false,
                "non_commercial" => $orderInvoiceService->isNonCommercial($order_id),
                "invoice" => $orderInvoiceService->getInvoiceOrder($order_id),
                'platform' => self::PLATAFORM,
                'reminder' => null
            )
        );

        $errors = $this->checkParamsBody($body, $orderId);

        if (!empty($errors)) {
            return [
                'success' => false,
                'errors' => $errors
            ];
        }

        $result = (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_ADD_CART,
            'POST',
            $body,
            true
        );

        if (array_key_exists('errors', $result)) {
            return $result;
        }

        return (new OrderQuotationService())->updateDataQuotation(
            $orderId,
            $result->id,
            $result->protocol,
            'pending',
            $shippingMethodId,
            null,
            $result->self_tracking
        );
    }

    /**
     * Function to remove order in cart by Melhor Envio.
     *
     * @param int $orderId
     * @return bool
     */
    public function remove($orderId)
    {
        $data = (new OrderQuotationService())->getData($orderId);

        if (!isset($data['order_id'])) {
            return false;
        }

        (new OrderQuotationService())->removeDataQuotation($orderId);

        (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_ADD_CART . '/' . $data['order_id'],
            'DELETE',
            []
        );

        $orderInCart = (new OrderService())->info($orderId);

        if (!$orderInCart['success']) {
            return true;
        }

        return false;
    }

    /**
     * Mount array with volumes by products.
     *
     * @param array $quotation
     * @param int $methodid
     * @return array $volumes
     */
    private function getVolumes($quotation, $methodId)
    {
        $volumes = [];

        foreach ($quotation as $item) {
            if ($item->id == $methodId) {
                foreach ($item->packages as $package) {
                    $volumes[] = [
                        'height' => $package->dimensions->height,
                        'width'  => $package->dimensions->width,
                        'length' => $package->dimensions->length,
                        'weight' => $package->weight,
                    ];
                }
            }
        }

        if ((new CalculateShippingMethodService())->isCorreios($methodId)) {
            return $volumes[0];
        }

        return $volumes;
    }

    /**
     * Sum values of products.
     *
     * @param array $products
     * @return float $value
     */
    private function getInsuranceValueByProducts($products)
    {
        $value = 0;

        foreach ($products as $product) {
            $value += ($product['unitary_value'] * $product['quantity']);
        }

        return $value;
    }

    /**
     * Function to validate params before send to request
     *
     * @param array $body
     * @return void
     */
    private function checkParamsBody($body, $orderId)
    {
        $errors = [];

        if ((new CalculateShippingMethodService())->isJadlog($body['service']) && empty($body['agency'])) {
            $errors[] = sprintf("Informar a agência Jadlog do envio %s", $orderId);
        }

        if (!array_key_exists("from", $body)) {
            $errors[] = sprintf("Informar origem do envio do pedido %s", $orderId);
        }

        if (!array_key_exists("to", $body)) {
            $errors[] = sprintf("Informar destino do envio do pedido %s", $orderId);
        }

        if (!array_key_exists("service", $body)) {
            $errors[] = sprintf("Informar o serviço do envio do pedido %s", $orderId);
        }

        if (!array_key_exists("products", $body)) {
            $errors[] = sprintf("Informar o produtos do envio do pedido %s", $orderId);
        }

        if (isset($body['service']) && $body['service'] >= 3 && !array_key_exists("agency", $body)) {
            $errors[] = sprintf("Informar a agência do envio do pedido %s", $orderId);
        }

        if (!isset($body['volumes'])) {
            $errors[] = sprintf("Informar os volumes do envio do pedido %s", $orderId);
        }

        return $errors;
    }
}
