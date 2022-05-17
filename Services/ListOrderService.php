<?php

namespace Services;

use Models\ShippingService;
use Helpers\TranslateStatusHelper;
use Helpers\ProductVirtualHelper;

class ListOrderService
{
    /**
     * Function to return the list of orders
     *
     * @param array $args
     * @return array
     */
    public function getList($args)
    {
        $limit = $args['limit'];
        $skip = $args['skip'];
        $status = $args['status'];
        $wpstatus = $args['wpstatus'];

        $posts = $this->getPosts($limit, $skip, $status, $wpstatus);

        if (empty($posts)) {
            return [
                'orders' => [],
                'load' => false
            ];
        }

        $orders = $this->getData($posts);

        return [
            'orders' => $orders,
            'load' => (count($orders) == ($limit) ?: 5) ? true : false
        ];
    }

    /**
     * Function to return the list of orders with the data of the Melhor Envio
     *
     * @param array $posts
     * @return array
     */
    private function getData($posts)
    {
        $orders = [];

        $statusMelhorEnvio = (new OrderService())->mergeStatus($posts);

        $quotationService = new QuotationService();

        $buyerService = new BuyerService();

        $translateHelper = new TranslateStatusHelper();

        $productService = new OrdersProductsService();

        foreach ($posts as $post) {
            $post_id = $post->ID;

            $invoice = (new InvoiceService())->getInvoice($post_id);

            $products = $productService->getProductsOrder($post_id);

            $products = ProductVirtualHelper::removeVirtuals($products);

            $orders[] = [
                'id' => $post_id,
                'tracking' => $statusMelhorEnvio[$post_id]['tracking'],
                'link_tracking' => (!is_null($statusMelhorEnvio[$post_id]['tracking']))
                    ? sprintf("https://www.melhorrastreio.com.br/rastreio/%s", $statusMelhorEnvio[$post_id]['tracking'])
                    : null,
                'to' => $buyerService->getDataBuyerByOrderId($post_id),
                'status' => $statusMelhorEnvio[$post_id]['status'],
                'status_texto' => $translateHelper->translateNameStatus(
                    $statusMelhorEnvio[$post_id]['status']
                ),
                'order_id' => $statusMelhorEnvio[$post_id]['order_id'],
                'service_id' =>  (!empty($statusMelhorEnvio[$post_id]['service_id']))
                    ? $statusMelhorEnvio[$post_id]['service_id']
                    : ShippingService::CORREIOS_SEDEX,
                'protocol' => $statusMelhorEnvio[$post_id]['protocol'],
                'non_commercial' => is_null($invoice['number']) || is_null($invoice['key']),
                'invoice'        => $invoice,
                'products' => $products,
                'quotation' => $quotationService->calculateQuotationByPostId($post_id),
                'link' => admin_url() . sprintf('post.php?post=%d&action=edit', $post_id)
            ];
        }

        return $orders;
    }

    /**
     * Function to get posts
     *
     * @param int $limit
     * @param int $skip
     * @param string $status
     * @param string $wpstatus
     * @return array posts
     */
    private function getPosts($limit, $skip, $status, $wpstatus)
    {
        $args = [
            'numberposts' => ($limit) ?: 5,
            'offset'      => ($skip) ?: 0,
            'post_type'   => 'shop_order',
        ];

        if (isset($wpstatus) && $wpstatus != 'all') {
            $args['post_status'] = $wpstatus;
        } else if (isset($wpstatus) && $wpstatus == 'all') {
            $args['post_status'] = array_keys(wc_get_order_statuses());
        } else {
            $args['post_status'] = 'publish';
        }

        if (isset($status) && $status != 'all') {
            $args['meta_query'] = [
                [
                    'key' => 'melhorenvio_status_v2',
                    'value' => sprintf(':"%s";', $status),
                    'compare' => 'LIKE'
                ]
            ];
        }

        return  get_posts($args);
    }
}
