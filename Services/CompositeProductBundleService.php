<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\DimensionsHelper;

 /**
  * Aqui é verificado se o produto é da classe do plugin Composite Product,
  * ou seja, esse produto possui regras para essa classe.
  * O produto pode ter as medidas da embalagem principal ou dos produtos interno da embalagem principal.
  * SHIPPING_FEE (post_meta shipping_fee)
  * - WHOLE = usa as medidas da caixa principal
  * - EACH = usa as medidas de cada produto de contém a embalagem principal.
  *
  * O produto pode ter o preço da embalagem principal,
  * da soma de dos produtos + embalagem ou apenas o preço dos prodtuos internos.
  * PRICING(post_meta pricing)
  * - ONLY = apenas o preço da embalagem princiapl
  * - INCLUDE = o preço da embalagem principal + preço dos produtos internos
  * - EXCLUDE = apenas o preços dos produtos internos.
  */
class CompositeProductBundleService {

	const PRODUCT_COMPOSITE = 'WC_Product_Composite';

	const PRODUCT_COMPOSITE_SHIPPING_FEE = 'wooco_shipping_fee';

	const PRODUCT_COMPOSITE_PRICING = 'wooco_pricing';

	const PRODUCT_COMPOSITE_SHIPPING_FEE_EACH = 'each';

	const PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE = 'whole';

	const PRODUCT_COMPOSITE_PRICING_INCLUDE = 'include';

	const PRODUCT_COMPOSITE_PRICING_EXCLUDE = 'exclude';

	const PRODUCT_COMPOSITE_PRICING_ONLY = 'only';

	protected $product;

	protected $item_product;

	protected $shipping_fee;

	protected $pricing;

	public function __construct( $item_product ) {
		$this->product = $item_product->get_product();

		$this->item_product = $item_product;

		if ( ! empty( $this->product ) ) {
			$this->shipping_fee = self::getShippingFeeType( $this->product->get_id() );
		}

		if ( ! empty( $this->product ) ) {
			$this->pricing = self::getPricingType( $this->product->get_id() );
		}
	}

	/**
	 * function to get products by Composite Bundle
	 *
	 * @return array $products|false
	 */
	public function getProductNormalize() {
		if ( $this->shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE ) {
			return array(
				'id'              => $this->product->get_id(),
				'name'            => $this->product->get_name(),
				'quantity'        => $this->item_product->get_quantity(),
				'unitary_value'   => round( $this->product->get_price(), 2 ),
				'insurance_value' => round( $this->product->get_price(), 2 ),
				'weight'          => DimensionsHelper::convertWeightUnit( $this->product->get_weight() ),
				'width'           => DimensionsHelper::convertUnitDimensionToCentimeter( $this->product->get_width() ),
				'height'          => DimensionsHelper::convertUnitDimensionToCentimeter( $this->product->get_height() ),
				'length'          => DimensionsHelper::convertUnitDimensionToCentimeter( $this->product->get_length() ),
				'shipping_fee'    => $this->shipping_fee,
				'pricing'         => $this->pricing,
			);
		}

		if ( $this->shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_EACH ) {
			return false;
		}
	}

	/**
	 *
	 * @param array $productsComposite
	 * @param array $products
	 * @return array
	 */
	public function selectProductsToReturnByTypeComposite( $productsComposite, $products ) {
		if ( is_null( array_values( $productsComposite )[0]['shipping_fee'] ) || is_null( array_values( $productsComposite )[0]['pricing'] ) ) {
			return $products;
		}

		$shipping_fee = array_values( $productsComposite )[0]['shipping_fee'];

		$pricing = array_values( $productsComposite )[0]['pricing'];

		if ( self::isCompositeWholeAndOnly( $productsComposite, $shipping_fee, $pricing ) ) {
			return $productsComposite;
		}

		if ( self::isCompositeWholeAndInclude( $productsComposite, $shipping_fee, $pricing ) ) {
			$value = 0;
			foreach ( $products as $product ) {
				$value += $product['insurance_value'];
			}
			foreach ( $productsComposite as $key => $product ) {
				$productsComposite[ $key ]['unitary_value']   = $value;
				$productsComposite[ $key ]['insurance_value'] = $value;

			}
			return $productsComposite;
		}

		if ( self::isCompositeWholeAndExclude( $productsComposite, $shipping_fee, $pricing ) ) {
			foreach ( $productsComposite as $key => $product ) {
				if ( ! empty( $_product ) ) {
					$productsComposite[ $key ]['unitary_value']   = $_product->get_price();
					$productsComposite[ $key ]['insurance_value'] = $_product->get_price();
				}
			}
			return $productsComposite;
		}

		return $products;
	}

	/**
	 * Function to check product is shippging == whole and pricing == 'only
	 *
	 * @param $productsComposite
	 * @param $shipping_fee
	 * @param $pricing
	 * @return bool
	 */
	public static function isCompositeWholeAndOnly( $productsComposite, $shipping_fee, $pricing ) {
		return (
			! empty( $productsComposite ) &&
			$shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE &&
			$pricing == self::PRODUCT_COMPOSITE_PRICING_ONLY
		);
	}

	/**
	 * Function to check product is shippging == whole and pricing == 'include'
	 *
	 * @param $productsComposite
	 * @param $shipping_fee
	 * @param $pricing
	 * @return bool
	 */
	public static function isCompositeWholeAndInclude( $productsComposite, $shipping_fee, $pricing ) {
		return (
			! empty( $productsComposite ) &&
			$shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE &&
			$pricing == self::PRODUCT_COMPOSITE_PRICING_INCLUDE
		);
	}

	/**
	 * Function to check product is shippging == whole and pricing == 'exclude'
	 *
	 * @param $productsComposite
	 * @param $shipping_fee
	 * @param $pricing
	 * @return bool
	 */
	public static function isCompositeWholeAndExclude( $productsComposite, $shipping_fee, $pricing ) {
		return (
			! empty( $productsComposite ) &&
			$shipping_fee == self::PRODUCT_COMPOSITE_SHIPPING_FEE_WHOLE &&
			$pricing == self::PRODUCT_COMPOSITE_PRICING_EXCLUDE
		);
	}

	/**
	 * Function to get type pricing
	 *
	 * @param int $product_id
	 * @return string
	 */
	public static function getPricingType( $product_id ) {
		return get_post_meta( $product_id, self::PRODUCT_COMPOSITE_PRICING, true );
	}

	/**
	 * Function to get type shipping fee
	 *
	 * @param int $product_id
	 * @return string
	 */
	public static function getShippingFeeType( $product_id ) {
		return get_post_meta( $product_id, self::PRODUCT_COMPOSITE_SHIPPING_FEE, true );
	}
}
