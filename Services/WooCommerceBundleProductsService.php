<?php

namespace Services;

use Helpers\DimensionsHelper;
use Services\ProductsService;

class WooCommerceBundleProductsService
{
    const OBJECT_WOOCOMMERCE_BUNDLE = 'WC_Product_Bundle';

    const OBJECT_PRODUCT_SIMPLE = 'WC_Product_Simple';

    const TYPE_LAYOUT_BUNDLE_DEFAULT = 'default';

    const BUNDLE_TYPE_EXTERNAL = 'external';

    const BUNDLE_TYPE_INTERNAL = 'internal';
    
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

            if (isset($data['stamp'])) {
            //Bundle Type: Unassembled
                if (isset($data['bundled_by'])) {
                    foreach ($data['stamp'] as $product) {
                        $products[$product['product_id']] = $productService->getProduct(
                            $product['product_id'], 
                            $items[$key]['quantity']
                        );
                    }
                    continue;
                }
                
                //Bundle Type: Assembled
                if (isset($data['bundled_items'])) {
                
                    $productId = $data['data']->get_id();
                    
                    //Assembled weight: Preserve Or Ignore
                    $weight = 0;
                    if ($data['data']->get_aggregate_weight()) {
                        foreach ($data['stamp'] as $product) {
                            $productInternal = $productService->getProduct(
                                $product['product_id'], 
                                $data['quantity']
                            );
                            $weight = $weight + (float) $productInternal->weight;
                        }
                    }

                    $productInternal = $productService->getProduct($productId,   $data['quantity']);
                    $productInternal->weight = (float) $productInternal->weight + $weight;
                    $products[$productId] = $productInternal;
                    continue;
                }
            }

            $products[] = $productService->getProduct(
                $data['product_id'], 
                $data['quantity']
            );
        }
        
        return $products;
    }

    /**
     * @param object $data
     * @return bool
     */
    private function isVirtualBundle($data)
    {
        return isset($data->virtual) && $data->virtual == 'yes';
    }

    /**
     * @param array $iemOrder
     * @return array
     */
    public function getMetas($itemOrder)
    {
        $metas = [];
        foreach ($itemOrder->get_meta_data() as $key => $item) {
            $data = $item->get_data();
            $metas[$data['key']] = $data['value'];
        }

        if (empty($metas['_bundled_items'])) {
            return [];
        }

        return $metas;
    }

    /**
     * @param array $meta
     * @return bool
     */
    public function isBundledItem($metas)
    {
        return !empty($metas);
    }

    /**
     * @param array $meta
     * @return string
     */
    public function getBundledItemType($metas)
    {
        if (!empty($metas['_bundle_weight'])) {
            return self::BUNDLE_TYPE_EXTERNAL;
        }

        return self::BUNDLE_TYPE_INTERNAL;
    }

    /**
     * @param array $stamp
     * @return array 
     */  
    public function getProducts($stamp)
    {
        $productService = new ProductsService();

        $products = [];
        foreach ($stamp as $product) {
            $products[$product['product_id']] = $productService->getProduct(
                $product['product_id'],
                $product['quantity']
            );
        }

        return $products;
    }

    /**
     * @param array $product
     * @param array $metas
     * @param array $products
     * @return array
     */
    public function getProductsInternal($product, $metas, $products)
    {
        if (empty($metas['_stamp'])) {
            return false;
        }
        $productsBundle = $this->getProducts($metas['_stamp']);

        if (empty($productsBundle)) {
            return $products;
        }

        if (empty($products)) {
            return $productsBundle;
        }
 
        return $products;
    }

    /**
     * @param array $product
     * @param array $metas
     * @return object
     */
    public function getProductExternal($product, $metas)
    {
        $productService = new ProductsService();

        $product = $productService->getProduct(
            $product['product_id'], 
            $product['quantity']
        );

        $product->weight = $metas['_bundle_weight'];

        return $product;
    }
}
