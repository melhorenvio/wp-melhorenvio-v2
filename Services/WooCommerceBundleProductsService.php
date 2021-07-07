<?php

namespace Services;

use Helpers\DimensionsHelper;

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
    public function getProductsByTypeBundle($data, $item)
    {
        if ($data->aggregate_weight) {
            return $this->getProductExternalWithAggregateWeight($data);
        }

        if (!$data->aggregate_weight && $data->layout == self::TYPE_LAYOUT_BUNDLE_DEFAULT) {
            return $this->getOnlyProductsInternalBundle($data);
        }  

        return $this->getProductExternalWithoutAggregateWeight($data);

    }

    /**
     * Function to normalize data of product 
     * 
     * @param object $product
     * @return object
     */
    public function normalizeProduct($product)
    {
        return (object) [
            "id" => $product->get_id(),
            "name" => $product->get_name(),
            "quantity" => 1, //todo: rever a quantidade.
            "unitary_value" => round($product->get_price(), 2),
            "insurance_value" => round($product->get_price(), 2),
            "weight" => DimensionsHelper::convertWeightUnit($product->get_weight()),
            "width" => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_width()),
            "height" => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_height()),
            "length" => DimensionsHelper::convertUnitDimensionToCentimeter($product->get_length()),
            "is_virtual" => $product->get_virtual()
        ];
    }

    /**
     * Function to get product of bundle with aggregted weight 
     * 
     * @param object $data
     * @return array
     */
    public function getProductExternalWithAggregateWeight($data)
    {
        $productBunblde = $data->get_data();
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

        $weigthExtra = $weigthExtra + floatval($productBunblde['weight']);

        $products[] = (object)  [
            "id" => $productBunblde['id'],
            "name" => $productBunblde['name'],
            "quantity" => 1, //todo: rever a quantidade.
            "unitary_value" => round($productBunblde['regular_price'], 2),
            "insurance_value" => round($productBunblde['regular_price'], 2),
            "weight" => DimensionsHelper::convertWeightUnit($weigthExtra),
            "width" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['width']),
            "height" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['height']),
            "length" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['length']),
            "is_virtual" => false
        ];

        return $products;
    }

     /**
     * Function to get product of bundle without aggregted weight 
     * 
     * @param object $data
     * @return array
     */
    public function getProductExternalWithoutAggregateWeight($data)
    {
        $productBunblde = $data->get_data();

        $products[] = (object)  [
            "id" => $productBunblde['id'],
            "name" => $productBunblde['name'],
            "quantity" => 1, //todo: rever a quantidade.
            "unitary_value" => round($productBunblde['regular_price'], 2),
            "insurance_value" => round($productBunblde['regular_price'], 2),
            "weight" => DimensionsHelper::convertWeightUnit($productBunblde['width']),
            "width" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['width']),
            "height" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['height']),
            "length" => DimensionsHelper::convertUnitDimensionToCentimeter($productBunblde['length']),
            "is_virtual" => false
        ];

        return $products;
    }

    /**
     * Function to get only products internal of bundle
     * 
     * @param object $data
     * @return array
     */
    public function getOnlyProductsInternalBundle($data)
    {
        $products = [];
        foreach ($data->get_bundled_items() as $key => $item) {
            $product_id = $item->data->get_product_id();
            if( !empty($product_id)) {
                $product = wc_get_product( $product_id );
                if (get_class($product) == self::OBJECT_PRODUCT_SIMPLE) {
                    $products[] = $this->normalizeProduct($product);
                }
            }
        }

        return $products;
    }

    /**
     * Function to get weight aggregate
     * 
     * @param object $data
     * @return float
     */
    public function aggregateWeightProducts($data)
    {
        $weigth = 0;
        foreach ($data->get_bundled_items() as $key => $item) {
            $product_id = $item->data->get_product_id(); 
            if( !empty($product_id)) {
                $product = wc_get_product( $product_id );
                $weigth = $weigth + floatval($product->get_weight());
            }
        }
        return $weigth;
    }

    /**
     * Function to get only products internal of bundle
     * 
     * @param object $data
     * @return array
     */
    public static function aggregateweightProductsNotMain($data)
    {
        $products = [];
        foreach ($data->get_bundled_items() as $key => $item_product) {
            $product_id = $item_product->data->get_product_id(); 
            if( !empty($product_id)) {
                $_product = wc_get_product( $product_id );
                $products[] = (object) [
                    "id" => $_product->get_id(),
                    "name" => $_product->get_name(),
                    "quantity" => $item_product->get_quantity(),
                    "unitary_value" => round($_product->get_price(), 2),
                    "insurance_value" => round($_product->get_price(), 2),
                    "weight" => DimensionsHelper::convertWeightUnit($_product->get_weight()),
                    "width" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_width()),
                    "height" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_height()),
                    "length" => DimensionsHelper::convertUnitDimensionToCentimeter($_product->get_length()),
                    "is_virtual" => $_product->get_virtual()
                ];
            }
        }

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
        foreach ($items as $key => $items) {
            foreach ($items as $key2 => $item) {
                if (in_array(get_class($item), [self::OBJECT_PRODUCT_SIMPLE, self::OBJECT_WOOCOMMERCE_BUNDLE])) {
                    $products[] = $item;
                }
            }
        }
        return $products;
    }
}
