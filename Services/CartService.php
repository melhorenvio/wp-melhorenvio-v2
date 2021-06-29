<?php

namespace Services;

use Models\Order;
use Models\Option;
use Models\Payload;
use Helpers\SessionHelper;

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
     * @return array
     */
    public function add($orderId, $products, $dataBuyer, $shippingMethodId)
    {
        $body = $this->createPayloadToCart($orderId, $products, $dataBuyer, $shippingMethodId);

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
            Order::STATUS_PENDING,
            $shippingMethodId,
            null,
            $result->self_tracking
        );
    }

    /**
     * Function to create payload to insert item on Cart Melhor Envio
     *
     * @param int $orderId
     * @param array $products
     * @param array $dataBuyer
     * @param int $shippingMethodId
     * @return array
     */
    public function createPayloadToCart($orderId, $products, $dataBuyer, $shippingMethodId)
    {
        $payloadSaved = (new Payload())->get($orderId);

        $products = (!empty($payloadSaved->products))
            ? $payloadSaved->products
            : $products;

        $products = $this->removeVirtualProducts($products);

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

        $insuranceValue = (!empty($insuranceRequired))
            ? (new ProductsService())->getInsuranceValue($products)
            : 0;

        return array(
            'from' => $dataFrom,
            'to' => $dataBuyer,
            'agency' => $this->getAgencyToInsertCart($shippingMethodId),
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
    }

    /**
     * Function to remove virtual product 
     * 
     * @param array $products
     * @return array
     */
    private function removeVirtualProducts($products)
    {
        foreach ($products as $key => $product) {
            if (isset($product['is_virtual']) && $product['is_virtual']) {
                unset($products[$key]);
            }
        }

        return $products;
    }

    /**
     * function to get agency selected by service_Id
     *
     * @param string $shippingMethodId
     * @return int|null
     */
    private function getAgencyToInsertCart($shippingMethodId)
    {
        $shippingMethodService = new CalculateShippingMethodService();

        if ($shippingMethodService->isJadlog($shippingMethodId)) {
            return (new AgenciesJadlogService())->getSelectedAgencyOrAnyByCityUser();
        }

        if ($shippingMethodService->isAzulCargo($shippingMethodId)) {
            return (new AgenciesAzulService())->getSelectedAgencyOrAnyByCityUser();
        }

        if ($shippingMethodService->isLatamCargo($shippingMethodId)) {
            return (new AgenciesLatamService())->getSelectedAgencyOrAnyByCityUser();
        }

        return null;
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

    /** 
     * Function to get data and information about items in the shopping cart
     * 
     * @return array
     */
    public function getInfoCart()
    {
        SessionHelper::initIfNotExists();

        global $woocommerce;

        $data = [];

        foreach($woocommerce->cart->get_cart() as $cart) {
            foreach($cart as $item) {
                if (gettype($item) == 'object')  {
                    $productId = $item->get_id();
                    if (!empty($productId)) {
                        $data['products'][$productId] = [
                            'name' => $item->get_name(),
                            'price' => $item->get_price()
                        ];

                        if (!empty($_SESSION['melhorenvio_additional'])) {
                            foreach($_SESSION['melhorenvio_additional'] as $dataSession) {
                                foreach($dataSession as $keyProduct =>$product) {
                                    $data['products'][$productId]['taxas_extras'] = $product;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($_SESSION['melhorenvio_additional'])) {
            $data['adicionais_extras'] = $_SESSION['melhorenvio_additional'];
        }

        return $data;
    }
}
