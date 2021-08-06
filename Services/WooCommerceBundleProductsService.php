<?php

namespace Services;

use Helpers\DimensionsHelper;
use Services\ProductsService;

class WooCommerceBundleProductsService
{
    const OBJECT_WOOCOMMERCE_BUNDLE = 'WC_Product_Bundle';

    const OBJECT_PRODUCT_SIMPLE = 'WC_Product_Simple';

    const TYPE_LAYOUT_BUNDLE_DEFAULT = 'default';
    
    /**
     * Function to check if a order is Bundle Product Class.
     * 
     * @param array $data
     * @return bool
     */
    public static function isWooCommerceProductBundle($data)
    {
        $item = end($data);
        return ((!empty($item['bundled_by']) || !empty($item['bundled_items'])) && !empty($item['stamp']));
    }

    /** 
     * Function to return produts by type bundle woocommerce used.
     * 
     * @param array $data
     * @param array item 
     * @return array
     */
    public function getProductsByTypeBundle($orderId, $data, $item)
    {   
        $products = [];
        $productService = new ProductsService();

        //todo: crate method isVirtual bundle.
        if (isset($data->virtual) && $data->virtual == 'yes') {
            foreach ($item->get_meta_data() as $dataItem) {
                $dataEachItem = $dataItem->get_data();
                if ($dataEachItem['key'] == '_stamp') {
                    if (!empty($dataEachItem['value'])) {
                        foreach ($dataEachItem['value'] as $product) {
                            $products[$product['product_id']] = $productService->getProduct(
                                $product['product_id'], 
                                $product['quantity']
                            );
                        }
                    }
                }
            }
            return $products;
        }

        $productBunblde = $data->get_data();

        $productExternal = [
            "id" => $productBunblde['id'],
            "name" => $productBunblde['name'],
            "quantity" => 1,
            "unitary_value" => round($productBunblde['regular_price'], 2),
            "insurance_value" => round($productBunblde['regular_price'], 2),
            "weight" => DimensionsHelper::convertWeightUnit($productBunblde['weight']),
            "width" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['width']),
            "height" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['height']),
            "length" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['length']),
            "is_virtual" => false
        ];

        if ($data->aggregate_weight) {
            $weigthExtra = 0;
            foreach ($data->get_bundled_items() as $key => $item_product) {
                $product_id = $item_product->data->get_product_id(); 
                if( !empty($product_id)) {
                    $product = wc_get_product( $product_id );
                    if (get_class($product) == self::OBJECT_PRODUCT_SIMPLE) {
                        $weigthExtra = $weigthExtra + floatval($product->get_weight());
                    }
                }
            }
            $productExternal['weight'] = $productExternal['weight'] + $weigthExtra;
        }
        $products[] = (object)  $productExternal;
        return $products;
    }

    /**
     * Function to manage products by bundle
     * 
     * @param array $items
     * @return array
     */
    public static function manageProductsBundle($items)
    {
        $products = [];
        $productService = new ProductsService();

        foreach ($items as $key => $data) {

            //Bundle Type: Unassembled
            if (isset($data['bundled_by']) && isset($data['stamp'])) {
                foreach ($data['stamp'] as $product) {
                    $products[$product['product_id']] = $productService->getProduct(
                        $product['product_id'], 
                        $product['quantity']
                    );
                }
            }
            
            //Bundle Type: Assembled
            if (isset($data['bundled_items']) && isset($data['stamp'])) {

                $productId = $data['data']->get_id();
                
                //Assembled weight: Preserve Or Ignore
                $weight = 0;
                if ($data['data']->get_aggregate_weight()) {
                    foreach ($data['stamp'] as $product) {
                        $productInternal = $productService->getProduct(
                            $product['product_id'], 
                            $product['quantity']
                        );
                        $weight = $weight + (float) $productInternal->weight;
                    }
                }

                $productInternal = $productService->getProduct($productId);
                $productInternal->weight = (float) $productInternal->weight + $weight;
                $products[$productId] = $productInternal;
            }
        }

        return $products;
    }
}
