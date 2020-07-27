<?php

namespace Controllers;

use Helpers\DimensionsHelper;

class PackageController
{
    /**
     * @param [type] $package
     * @return void
     */
    public function getPackage($package)
    {
        $weight = 0;
        $width = 0;
        $height = 0;
        $length = 0;

        foreach ($package['contents'] as $item_id => $values) {

            $_product = $values['data'];
            $weight = $weight + $_product->get_weight() * $values['quantity'];

            $width  += $_product->width;
            $height += $_product->height;
            $length += $_product->length;
        }

        return $this->converterIfNecessary([
            "weight" => $weight,
            "width" => $width,
            "height" => $height,
            "length" => $length
        ]);
    }

    /**
     * @param [type] $order_id
     * @return void
     */
    public function getPackageOrderAfterCotation($order_id)
    {
        $data = get_post_meta($order_id, 'melhorenvio_cotation_v2');

        $data = end($data);
        //return $data;
        $packages = [];

        if (is_array($data)) {
            foreach ($data as $item) {

                if (isset($item->volumes) && !empty($item->volumes)) {

                    $total = $this->countTotalvolumes($item->volumes);
                    $volumes = count($item->volumes);
                    $v = 1;
                    foreach ($item->volumes as $package) {

                        $quantity = (isset($package->products[0]->quantity)) ? $package->products[0]->quantity : 1;
                        $weight = (isset($package->weight)) ? $package->weight : null;

                        $packages[$item->id][] = [
                            'volume' => $v,
                            'width'  => (isset($package->width)) ? $package->width : null,
                            'height' => (isset($package->height)) ? $package->height : null,
                            'length' => (isset($package->length)) ? $package->length : null,
                            'weight' => $this->getWeighteBox($total, $quantity, $weight),
                            'quantity' => $quantity,
                            'insurance_value' => (isset($package->price) ? $package->price : 1.0 ),
                            'insurance' => $package->insurance,
                            'products' => isset($package->products) ? $package->products : []
                        ];

                        $v++;
                    }
                }
            }
        }

        return $packages;
    }

    private function countTotalvolumes($data)
    {
        $total = 0;
        foreach ($data as $item) {
            if (isset($item->products)) {
                foreach($item->products as $prod) {
                    $total = $total + $prod->quantity;
                }
            }
        }
        return $total;
    }

    private function getWeighteBox($total, $quantity, $value)
    {
        return $value;
    }

    /**
     * @param [type] $order_id
     * @return void
     */
    public function getPackageOrder($order_id)
    {
        $weight = 0;
        $width  = 0;
        $height = 0;
        $length = 0;
        $order  = wc_get_order( $order_id );

        foreach( $order->get_items() as $item_id => $item_product ){
            
            $_product = $item_product->get_product();

            $weight = $weight + $_product->weight * $item_product->get_quantity();
            $width  += $_product->width;
            $height += $_product->height;
            $length += $_product->length;
        }

        return $this->converterIfNecessary([
            "weight" => $weight,
            "width"  => $width,
            "height" => $height,
            "length" => $length
        ]);
    }

    /**
     * @param [type] $package
     * @return void
     */
    private function converterIfNecessary($package)
    {
        $weight_unit = get_option('woocommerce_weight_unit');
        if ($weight_unit == 'g') {
            $package['weight'] = $package['weight'] / 1000;
        }

        $package['width'] = (new DimensionsHelper())->converterDimension($package['width']);
        $package['height'] = (new DimensionsHelper())->converterDimension($package['height']);
        $package['length'] = (new DimensionsHelper())->converterDimension($package['length']);

        return $package;
    }
}
