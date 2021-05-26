<?php

namespace Services;

use Models\Order;
use Models\Option;
use Models\Payload;
use Helpers\SessionHelper;
use Helpers\PostalCodeHelper;
use Helpers\CpfHelper;

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

        $errors = $this->validatePayloadBeforeAddCart($body, $orderId);

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
            ? $methodService->insuranceValueIsRequired($options->use_insurance_value, $shippingMethodId)
            : true;

        $insuranceValue = (!empty($insuranceRequired))
            ? (new ProductsService())->getInsuranceValue($products)
            : 0;

        $payload = array(
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

        return $payload;
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
     * @return array
     */
    private function validatePayloadBeforeAddCart($body, $orderId)
    {
        $errors = [];

        if (empty($body['service'])) {
            $errors[] = 'Informar o serviço de envio.';
        }

        $isCorreios = (new CalculateShippingMethodService())->isCorreios($body['service']);

        if (empty($body['from'])) {
            $errors[] = 'Informar o remetente o pedido.';            
        }

        if (!empty($body['from']) && empty($body['from']->name)) {
            $errors[] = 'Informar o nome do remetente do pedido.';
        }

        if (!empty($body['from']) && empty($body['from']->phone) && !$isCorreios) {
            $errors[] = 'Informar o nome do remetente do pedido.';
        }

        if (!empty($body['from']) && empty($body['from']->email) && !$isCorreios) {
            $errors[] = 'Informar o e-mail do remetente do pedido.';
        }

        if (!empty($body['from']) && empty($body['from']->document) && !$isCorreios) {
            $errors[] = 'Informar o documento do remetente do pedido.';
        }

        if (!empty($body['from']->document)) {
            if (!CpfHelper::validate($body['from']->document)) {
                $errors[] = sprintf("O CPF %s do remetente não é válido", $body['from']->document);
            }
        }

        if (!empty($body['from']) && empty($body['from']->address)) {
            $errors[] = 'Informar o endereço do remetente do pedido.';
        }

        if (!empty($body['from']) && empty($body['from']->number)) {
            $errors[] = 'Informar o número do endereço do remetente do pedido.';
        }

        if (!empty($body['from']) && empty($body['from']->city)) {
            $errors[] = 'Informar a cidade do remetente do pedido.';
        }

        if (!empty($body['from']) && empty($body['from']->state_abbr)) {
            $errors[] = 'Informar o estado do remetente do pedido.';
        }

        if (empty($body['from']->postal_code)) {
            $errors[] = 'Informar o CEP do remetente do pedido.';
        }

        if (!empty($body['from']->postal_code)) {
            $body['from']->postal_code = PostalCodeHelper::postalcode($body['from']->postal_code);
            if (strlen($body['from']->postal_code) != PostalCodeHelper::SIZE_POSTAL_CODE) {
                $errors[] = 'CEP do rementente incorreto.';
            }
        }

        if (empty($body['to'])) {
            $errors[] = 'Informar o destinatário o pedido.';            
        }

        if (!empty($body['to']) && empty($body['to']->name)) {
            $errors[] = 'Informar o nome do destinatário do pedido.';
        }

        if (!empty($body['to']) && empty($body['to']->phone) && !$isCorreios) {
            $errors[] = 'Informar o nome do destinatário do pedido.';
        }

        if (!empty($body['to']) && empty($body['to']->email) && !$isCorreios) {
            $errors[] = 'Informar o e-mail do destinatário do pedido.';
        }

        if (!empty($body['to']) && empty($body['to']->document) && !$isCorreios) {
            $errors[] = 'Informar o documento do destinatário do pedido.';
        }

        if (!empty($body['to']->document)) {
            if (!CpfHelper::validate($body['to']->document)) {
                $errors[] = sprintf("O CPF %s do destinatário não é válido", $body['to']->document);
            }
        }

        if (!empty($body['to']) && empty($body['to']->address)) {
            $errors[] = 'Informar o endereço do destinatário do pedido.';
        }

        if (!empty($body['to']) && empty($body['to']->number)) {
            $errors[] = 'Informar o número do endereço do destinatário do pedido.';
        }

        if (!empty($body['to']) && empty($body['to']->city)) {
            $errors[] = 'Informar a cidade do destinatário do pedido.';
        }

        if (!empty($body['to']) && empty($body['to']->state_abbr)) {
            $errors[] = 'Informar o estado do destinatário do pedido.';
        }

        if (empty($body['to']->postal_code)) {
            $errors[] = 'Informar o CEP do destinatário do pedido.';
        }

        if (!empty($body['to']->postal_code)) {
            $body['to']->postal_code = PostalCodeHelper::postalcode($body['to']->postal_code);
            if (strlen($body['to']->postal_code) != PostalCodeHelper::SIZE_POSTAL_CODE) {
                $errors[] = 'CEP do destinatário incorreto.';
            }
        }

        if (!$isCorreios && empty($body['agency'])) {
            $errors[] = 'É necessário informar a agência de postagem para esse serviço de envio';
        }

        if (empty($body['products'])) {
            $errors[]  = 'É necessário informar os produtos do envio.';
        }

        if (!empty($body['products'])) {
            foreach  ($body['products'] as $key => $product) {
            
                if (empty($product['name'])) {
                    $errors[] = sprintf("Infomar o nome do produto %d", $key++);
                }

                if (empty($product['quantity'])) {
                    $errors[] = sprintf("Infomar a quantidade do produto %d", $key++);
                }

                if (empty($product['unitary_value'])) {
                    $errors[] = sprintf("Infomar o valor unitário do produto %d", $key++);
                }

                if (empty($product['weight'])) {
                    $errors[] = sprintf("Infomar o peso do produto %d", $key++);
                }

                if (empty($product['width'])) {
                    $errors[] = sprintf("Infomar a largura do produto %d", $key++);
                }

                if (empty($product['height'])) {
                    $errors[] = sprintf("Infomar a altura do produto %d", $key++);
                }

                if (empty($product['length'])) {
                    $errors[] = sprintf("Infomar o comprimento do produto %d", $key++);
                }
            }
        }

        if (empty($body['volumes'])) {
            $errors[] = 'Informar o(s) volume(s) do envio.';
        }

        if (!empty($body['volumes'])) {
            if (empty($body['volumes']['height'])) {
                $errors[] ="Informar a altura do volume.";
            }

            if (empty($body['volumes']['width'])) {
                $errors[] = "Informar a largura do volume.";
            }

            if (empty($body['volumes']['length'])) {
                $errors[] = "Informar o comprimento do volume.";
            }

            if (empty($body['volumes']['weight'])) {
                $errors[] = "Informar o peso do volume.";
            }
        }

        if (empty($body['options'])) {
            $errors[] = 'Informar os opcionais do envio.';
        }

        if (empty($body['options'])) {
            $errors[] = 'Informar os opcionais do envio.';
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
