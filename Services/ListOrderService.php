<?php

namespace Services;

use Helpers\TranslateStatusHelper;

class ListOrderService
{
    public function getList($args)
    {
        $limit = $_GET['limit'];
        $skip = $_GET['skip'];
        $status = $_GET['status'];
        $wpstatus = $_GET['wpstatus'];

        $posts = $this->getPosts($limit, $skip, $status, $wpstatus);

        if (empty($posts)) {
            return [
                'orders' => [],
                'load' => false
            ];
        }

        $orders = $this->setData($posts);

        return [
            'orders' => $orders,
            'load' => (count($orders) == ($limit) ?: 5) ? true : false
        ];
    }

    private function setData($posts)
    {
        $orders = [];

        $statusMelhorEnvio = (new OrderService())->mergeStatus($posts);

        foreach ($posts as $post) {

            $post_id = $post->ID;

            $invoice = (new InvoiceService())->getInvoice($post_id);

            $orders[] = [
                'id' => $post_id,
                'tracking' => $statusMelhorEnvio[$post_id]['tracking'],
                'link_tracking' => (!is_null($statusMelhorEnvio[$post_id]['tracking'])) ? sprintf("https://www.melhorrastreio.com.br/rastreio/%s", $statusMelhorEnvio[$post_id]['tracking']) : null,
                'to' => (new BuyerService())->getDataBuyerByOrderId($post->ID),
                'status' => $statusMelhorEnvio[$post_id]['status'],
                'status_texto' => (new TranslateStatusHelper())->translateNameStatus($statusMelhorEnvio[$post_id]['status']),
                'order_id' => $statusMelhorEnvio[$post_id]['order_id'],
                'protocol' => $statusMelhorEnvio[$post_id]['protocol'],
                'non_commercial' => (is_null($invoice['number']) || is_null($invoice['key'])) ? true : false,
                'invoice'        => $invoice,
                'products' => (new OrdersProductsService())->getProductsOrder($post_id),
                'cotation' => [],
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

        if (isset($$status) && $$status != 'all') {
            $args['meta_query'] = [
                [
                    'key' => 'melhorenvio_status_v2',
                    'value' => sprintf(':"%s";', $$status),
                    'compare' => 'LIKE'
                ]
            ];
        }

        return  get_posts($args);
    }
}
