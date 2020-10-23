<?php

namespace Services;

use Models\Option;
use Models\Payload;

class CartService
{
    const PLATAFORM = 'WooCommerce V2';

    const ROUTE_MELHOR_ENVIO_ADD_CART = '/cart';

    /**
     * Function to add item on Cart Melhor Envio
     *
     * @param int $orderId
     * @param array $products
     * @param array $dataBuyer
     * @param int $shippingMethodId
     * @return void
     */
    public function add($orderId, $products, $dataBuyer, $shippingMethodId)
    {
        $payloadSaved = (new Payload())->get($orderId);

        $products = (!empty($payloadSaved->products))
            ? $payloadSaved->products
            : $products;

        $dataBuyer = (!empty($payloadSaved->buyer))
            ? $payloadSaved->buyer
            : $dataBuyer;

        $dataFrom =  (new SellerService())->getData();

        $quotation = (new QuotationService())->calculateQuotationByPostId($orderId);

        $orderInvoiceService = new OrderInvoicesService();

        $methodService = new CalculateShippingMethodService();

        $options = (!empty($payloadSaved->options))
            ? $payloadSaved->options
            : (new Option())->getOptions();

        $insuranceRequired = ($methodService->isCorreios($shippingMethodId))
            ? $methodService->insuranceValueIsRequired($options->insurance_value, $shippingMethodId)
            : true;

        $insuranceValue = ($insuranceRequired)
            ? (new ProductsService())->getInsuranceValue($products)
            : 0;

        $body = array(
            'from' => $dataFrom,
            'to' => $dataBuyer,
            'agency' => (new AgenciesJadlogService())->getSelectedAgencyOrAnyByCityUser(),
            'service' => $shippingMethodId,
            'products' => $products,
            'volumes' => $this->getVolumes($quotation, $shippingMethodId),
            'options' => array(
                "insurance_value" => $insuranceValue,
                "receipt" => $options->receipt,
                "own_hand" => $options->own_hand,
                "collect" => false,
                "reverse" => false,
                "non_commercial" => $orderInvoiceService->isNonCommercial($orderId),
                "invoice" => $orderInvoiceService->getInvoiceOrder($orderId),
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

        if (!empty($result->errors)) {
            return [
                'success' => false,
                'errors' => end($result->errors)
            ];
        }

        if (empty($result->id)) {
            return [
                'success' => false,
                'errors' => 'Não foi possível enviar o pedido para o carrinho de compras'
            ];
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
     * @param int $postId
     * @param string $orderId
     * @return bool
     */
    public function remove($postId, $orderId)
    {
        (new OrderQuotationService())->removeDataQuotation($postId);

        (new RequestService())->request(
            self::ROUTE_MELHOR_ENVIO_ADD_CART . '/' . $orderId,
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
            if (!isset($item->id)) {
                continue;
            }
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

        if (!isset($body['volumes'])) {
            $errors[] = sprintf("Informar os volumes do envio do pedido %s", $orderId);
        }

        return $errors;
    }
}
