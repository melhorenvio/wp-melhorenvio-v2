<?php

namespace Controllers;
use Controllers\PackageController;

class CotationController {

    public function __construct() {
        //woocommerce_checkout_update_order_review ~> use this action for check when alter shipping method
        //woocommerce_checkout_order_processed ~> use this in prodution
        add_action('woocommerce_checkout_order_processed', array($this, 'makeCotationOrder'));
    }

    public function makeCotationOrder($order_id) {

        global $woocommerce;
        $to = str_replace('-', '', $woocommerce->customer->get_shipping_postcode());

        $packagecontroller  = new PackageController();
        $package = $packagecontroller->getPackageOrder($order_id);

        $result = $this->makeCotationPackage($package, [1,2,3,4,7], $to);
        $result['date_cotation'] = date('Y-m-d H:i:s');
        $result['choose_method'] =$this->getCodeShippingSelected(end($woocommerce->session->get( 'chosen_shipping_methods')));

        add_post_meta($order_id, 'melhorenvio_cotation_v2', $result);
    }

    private function getCodeShippingSelected($choose) {
        switch ($choose) {
            case 'melhorenvio_pac':
                return 1;
                break;
            case 'melhorenvio_sedex':
                return 2;
                break;
            default:
                return 0;
        }
    }

    public function makeCotationPackage($package, $services, $to) {
        return $this->makeCotation($to, $services, $package, []);
    } 

    protected function makeCotation($to, $services, $package, $options) {

        // TODO
        $token = get_option('melhorenvio_token');
        $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjFmZWEzMzIzMzQ1ZTQ4MGEzM2JiZmFkMzk4ZTU2MzFkYWNjNWZjODM5MTY2ZWU2ZDdlNDg0NmE5ODM3YjYyZWZmMTAzMzlmMjIzNjliMTFlIn0.eyJhdWQiOiIxIiwianRpIjoiMWZlYTMzMjMzNDVlNDgwYTMzYmJmYWQzOThlNTYzMWRhY2M1ZmM4MzkxNjZlZTZkN2U0ODQ2YTk4MzdiNjJlZmYxMDMzOWYyMjM2OWIxMWUiLCJpYXQiOjE1MzczODgxOTIsIm5iZiI6MTUzNzM4ODE5MiwiZXhwIjoxNTY4OTI0MTkyLCJzdWIiOiJkZmZkN2EyYy0xMzYzLTQ4ZWQtOGFkYy00ZWZiOGZlNjI5ZWEiLCJzY29wZXMiOlsiY2FydC1yZWFkIiwiY2FydC13cml0ZSIsImNvbXBhbmllcy1yZWFkIiwiY29tcGFuaWVzLXdyaXRlIiwiY291cG9ucy1yZWFkIiwiY291cG9ucy13cml0ZSIsIm5vdGlmaWNhdGlvbnMtcmVhZCIsIm9yZGVycy1yZWFkIiwicHJvZHVjdHMtcmVhZCIsInByb2R1Y3RzLXdyaXRlIiwicHVyY2hhc2VzLXJlYWQiLCJzaGlwcGluZy1jYWxjdWxhdGUiLCJzaGlwcGluZy1jYW5jZWwiLCJzaGlwcGluZy1jaGVja291dCIsInNoaXBwaW5nLWNvbXBhbmllcyIsInNoaXBwaW5nLWdlbmVyYXRlIiwic2hpcHBpbmctcHJldmlldyIsInNoaXBwaW5nLXByaW50Iiwic2hpcHBpbmctc2hhcmUiLCJzaGlwcGluZy10cmFja2luZyIsImVjb21tZXJjZS1zaGlwcGluZyIsInRyYW5zYWN0aW9ucy1yZWFkIiwidXNlcnMtcmVhZCIsInVzZXJzLXdyaXRlIiwid2ViaG9va3MtcmVhZCIsIndlYmhvb2tzLXdyaXRlIl19.rzJqqNkqVvJHMD1wnKXa1xmLLSauZOV2KHlo2najYvTnllsYX8aqlC8Q4VRLphSRJB3cXjB_lgmxnplGJPMrpPhHZ3hwGBISWWDiMny1Pfam-4crLQsPqu4YZn8e2PN4IKUW7Zlx6c2ZGb0cGNtsTsNPFir4vthhQlb1y2rFLWKUW34Le0rBQJE4aOrlf74jD8yG7gQmbZXHgzYFg0Xvdj43zcHZpZkm6gSvtH-QFYJ1FuN56pWIXdcszeUTHJXdw7M6T-m2wD2Kt4I5DpeEfuYnmVw4R72KbSyLSDNLdmqM2hAJaSyiZq1KWAMp-vhEtVkBfBHxm2C2W0xRwJKpvjjGC176U5sQ5ZKbuqkrUhd3Xo4BYjlpodRC4E_jeqrVkBvOVt299YTxg_l-YdIpOWuRAyp6MDZbTGsmdgZVleQkMs5myBtLozOsCyJELWqCauVihj8S33VTlWM0BSg_n8siZ_CJb7UutCAMW_mLBEwA6ASji0zv1ojo0xz2sTQSq4vhW8bMg35zktyoLJCBIBKN81RXhoH_wwPyP8iYg8EU_l4yhfzP_MYQj8D4_Kq_EAM9DXXGvC149BRaxiCjSP76FiC0WfHE8BrnfFUylVXCLk5nF3xee5fDmfD-kd8ZFj_NbxhMzqReo416f4JnFX26Od7vM0sEbQqzsBzLdnA';
        
        $defaultoptions = [
            "insurance_value" => $this->total,
            "receipt"         => false, 
            "own_hand"        => false, 
            "collect"         => false 
        ];
        $opts = array_merge($defaultoptions, $options);

        $body = [
            "from" => [
                "postal_code" => '01023001' // TODO
            ],
            'to' => [
                'postal_code' => $to
            ],
            'package' => $package,
            'options' => $opts,
            "services" => $this->converterArrayToCsv($services)
        ];

        $params = array(
            'headers'           =>  [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'body'  => json_encode($body),
            'timeout'=>10
        );

        $response =  json_decode(wp_remote_retrieve_body(wp_remote_post('https://www.melhorenvio.com.br/api/v2/me/shipment/calculate', $params)));
        return $response;

    }

    private function converterArrayToCsv($services) {
        $string = '';

        foreach ($services as $service) {
            $string .= $service . ',';
        }

        return rtrim($string,",");
    }

    private function normalizeToArray($data) {
        $result = [];
        foreach ($data as $item) {
            $packages = [];
            if (!empty($item->packages)) {
                foreach ($item->packages as $pack) { 
                    $products = [];
                    foreach ($pack->products as $product) {
                        $products[] = [
                            'id' => $product->id,
                            'quantity' => $product->quantity
                        ];
                    }
                    $packages[] = [
                        'price' => $pack->price,
                        'discount' => $pack->discount,
                        'format' => $pack->format,
                        'dimensions' => [
                            'height' => $pack->dimensions->height,
                            'width' => $pack->dimensions->width,
                            'length' => $pack->dimensions->length
                        ],
                        'weight' => $pack->weight,
                        'insurance_value' => $pack->insurance_value,
                        'products' => $products
                    ];
                }
            }
    
            $result[] = [
                'id' => $item->id,
                'name' => $item->name,
                'price' => $item->price,
                'delivery_time' => $item->delivery_time,
                'currency' => $item->currency,
                'delivery_range' => [
                    'min' => $item->delivery_range->min,
                    'max' => $item->delivery_range->max
                ],
                'packages' => $packages,
                'additional_services' => [
                    'receipt' => $item->additional_services->receipt,
                    'own_hand' => $item->additional_services->own_hand,
                    'collect' => $item->additional_services->collect
                ],
                'company' => [
                    'id' => $item->company->id,
                    'name' => $item->company->name
                ],
                'selected' => $item->selected,
                // 'postcode' => $postcode,
                // 'postcode_client' => $postcodeClient
            ];
        }
        return $result;
    }
}

$cotationcontroller = new CotationController();
