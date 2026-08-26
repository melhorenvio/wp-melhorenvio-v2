<?php

declare(strict_types=1);

namespace MelhorEnvio\Database\Seeders;

final class WooCommerceSeeder implements SeederInterface {

	public function run(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			throw new \RuntimeException( 'WooCommerce plugin is not installed or activated.' );
		}

		$this->addProducts();
		$this->setupPages();
		$this->setupPayments();
		$this->setupTaxes();
		$this->setupShipping();
		$this->completeSetup();
		$this->addOrders();
	}

	private function addProducts(): void {
		$products = array(
			array(
				'name'              => 'Produto de Exemplo 1',
				'description'       => 'Este é um produto de exemplo criado pelo seeder.',
				'short_description' => 'Produto de exemplo',
				'price'             => '29.90',
				'regular_price'     => '29.90',
				'sku'               => 'PROD-001',
				'weight'            => '0.3',
				'length'            => '15',
				'width'             => '10',
				'height'            => '5',
				'stock'             => 100,
			),
			array(
				'name'              => 'Produto de Exemplo 2',
				'description'       => 'Outro produto de exemplo para sua loja.',
				'short_description' => 'Segundo produto',
				'price'             => '49.90',
				'regular_price'     => '49.90',
				'sku'               => 'PROD-002',
				'weight'            => '0.5',
				'length'            => '20',
				'width'             => '15',
				'height'            => '8',
				'stock'             => 50,
			),
			array(
				'name'              => 'Produto de Exemplo 3',
				'description'       => 'Mais um produto para completar sua loja.',
				'short_description' => 'Terceiro produto',
				'price'             => '79.90',
				'regular_price'     => '79.90',
				'sku'               => 'PROD-003',
				'weight'            => '1.2',
				'length'            => '30',
				'width'             => '20',
				'height'            => '12',
				'stock'             => 30,
			),
			array(
				'name'              => 'Camiseta Básica',
				'description'       => 'Camiseta de algodão leve, ideal para testar frete de itens pequenos.',
				'short_description' => 'Camiseta de algodão',
				'price'             => '39.90',
				'regular_price'     => '39.90',
				'sku'               => 'PROD-004',
				'weight'            => '0.2',
				'length'            => '25',
				'width'             => '20',
				'height'            => '3',
				'stock'             => 200,
			),
			array(
				'name'              => 'Caneca Personalizada',
				'description'       => 'Caneca de cerâmica 300ml, item frágil para testar embalagem.',
				'short_description' => 'Caneca de cerâmica',
				'price'             => '24.90',
				'regular_price'     => '24.90',
				'sku'               => 'PROD-005',
				'weight'            => '0.4',
				'length'            => '12',
				'width'             => '12',
				'height'            => '10',
				'stock'             => 80,
			),
			array(
				'name'              => 'Tênis Esportivo',
				'description'       => 'Tênis para corrida, item de tamanho médio para cálculo de frete.',
				'short_description' => 'Tênis para corrida',
				'price'             => '199.90',
				'regular_price'     => '249.90',
				'sku'               => 'PROD-006',
				'weight'            => '0.9',
				'length'            => '33',
				'width'             => '22',
				'height'            => '13',
				'stock'             => 40,
			),
			array(
				'name'              => 'Mochila Resistente',
				'description'       => 'Mochila 30L, item volumoso para testar frete por dimensões.',
				'short_description' => 'Mochila 30 litros',
				'price'             => '149.90',
				'regular_price'     => '149.90',
				'sku'               => 'PROD-007',
				'weight'            => '0.8',
				'length'            => '45',
				'width'             => '30',
				'height'            => '20',
				'stock'             => 25,
			),
			array(
				'name'              => 'Cadeira de Escritório',
				'description'       => 'Cadeira ergonômica, item pesado e grande para testar limites de frete.',
				'short_description' => 'Cadeira ergonômica',
				'price'             => '599.90',
				'regular_price'     => '699.90',
				'sku'               => 'PROD-008',
				'weight'            => '12.5',
				'length'            => '60',
				'width'             => '60',
				'height'            => '110',
				'stock'             => 10,
			),
		);

		foreach ( $products as $productData ) {
			$existingProductId = wc_get_product_id_by_sku( $productData['sku'] );

			if ( $existingProductId ) {
				continue;
			}

			$product = new \WC_Product_Simple();
			$product->set_name( $productData['name'] );
			$product->set_description( $productData['description'] );
			$product->set_short_description( $productData['short_description'] );
			$product->set_regular_price( $productData['regular_price'] );
			$product->set_price( $productData['price'] );
			$product->set_sku( $productData['sku'] );
			$product->set_weight( $productData['weight'] );
			$product->set_length( $productData['length'] );
			$product->set_width( $productData['width'] );
			$product->set_height( $productData['height'] );
			$product->set_manage_stock( true );
			$product->set_stock_quantity( $productData['stock'] );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'visible' );
			$product->set_stock_status( 'instock' );
			$product->save();
		}
	}

	private function addOrders(): void {
		$orders = array(
			array(
				'reference' => 'SEED-ORDER-001',
				'status'    => 'processing',
				'items'     => array(
					array( 'sku' => 'PROD-001', 'qty' => 2 ),
					array( 'sku' => 'PROD-004', 'qty' => 1 ),
				),
			),
			array(
				'reference' => 'SEED-ORDER-002',
				'status'    => 'completed',
				'items'     => array(
					array( 'sku' => 'PROD-006', 'qty' => 1 ),
				),
			),
			array(
				'reference' => 'SEED-ORDER-003',
				'status'    => 'on-hold',
				'items'     => array(
					array( 'sku' => 'PROD-007', 'qty' => 1 ),
					array( 'sku' => 'PROD-005', 'qty' => 3 ),
				),
			),
			array(
				'reference' => 'SEED-ORDER-004',
				'status'    => 'pending',
				'items'     => array(
					array( 'sku' => 'PROD-008', 'qty' => 1 ),
				),
			),
		);

		$address = array(
			'first_name' => 'João',
			'last_name'  => 'da Silva',
			'company'    => '',
			'email'      => 'cliente@exemplo.com',
			'phone'      => '11999999999',
			'address_1'  => 'Av. Paulista, 1000',
			'address_2'  => 'Apto 101',
			'city'       => 'São Paulo',
			'state'      => 'SP',
			'postcode'   => '01310-100',
			'country'    => 'BR',
		);

		foreach ( $orders as $orderData ) {
			$existing = wc_get_orders(
				array(
					'limit'      => 1,
					'return'     => 'ids',
					'meta_key'   => '_wpmi_seed_reference',
					'meta_value' => $orderData['reference'],
				)
			);

			if ( ! empty( $existing ) ) {
				continue;
			}

			$order = wc_create_order();

			foreach ( $orderData['items'] as $item ) {
				$productId = wc_get_product_id_by_sku( $item['sku'] );

				if ( ! $productId ) {
					continue;
				}

				$product = wc_get_product( $productId );

				if ( ! $product ) {
					continue;
				}

				$order->add_product( $product, $item['qty'] );
			}

			$order->set_address( $address, 'billing' );
			$order->set_address( $address, 'shipping' );

			$shippingItem = new \WC_Order_Item_Shipping();
			$shippingItem->set_method_title( 'Frete Fixo' );
			$shippingItem->set_method_id( 'flat_rate' );
			$shippingItem->set_total( '15.00' );
			$order->add_item( $shippingItem );

			$order->set_payment_method( 'cod' );
			$order->set_payment_method_title( 'Pagamento na Entrega' );
			$order->calculate_totals();
			$order->update_status( $orderData['status'], 'Pedido criado pelo seeder.' );
			$order->update_meta_data( '_wpmi_seed_reference', $orderData['reference'] );
			$order->save();
		}
	}

	private function setupPages(): void {
		if ( function_exists( 'wc_create_page' ) ) {
			$pages = array(
				'shop'      => array(
					'name'    => 'Loja',
					'title'   => 'Loja',
					'content' => '',
					'option'  => 'woocommerce_shop_page_id',
				),
				'cart'      => array(
					'name'    => 'Carrinho',
					'title'   => 'Carrinho',
					'content' => '[woocommerce_cart]',
					'option'  => 'woocommerce_cart_page_id',
				),
				'checkout'  => array(
					'name'    => 'Finalizar Compra',
					'title'   => 'Finalizar Compra',
					'content' => '[woocommerce_checkout]',
					'option'  => 'woocommerce_checkout_page_id',
				),
				'myaccount' => array(
					'name'    => 'Minha Conta',
					'title'   => 'Minha Conta',
					'content' => '[woocommerce_my_account]',
					'option'  => 'woocommerce_myaccount_page_id',
				),
			);

			foreach ( $pages as $slug => $pageData ) {
				$pageId = wc_create_page(
					$slug,
					$pageData['option'],
					$pageData['name'],
					$pageData['content'],
					0
				);

				if ( $pageId ) {
					update_option( $pageData['option'], $pageId );
				}
			}
		}

		$shopPageId = get_option( 'woocommerce_shop_page_id' );
		if ( $shopPageId && ! get_option( 'page_on_front' ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $shopPageId );
		}
	}

	private function setupPayments(): void {
		$paymentGateways = WC()->payment_gateways();
		$gateways        = $paymentGateways->payment_gateways();

		if ( isset( $gateways['bacs'] ) ) {
			$gateways['bacs']->enabled     = 'yes';
			$gateways['bacs']->title       = 'Transferência Bancária';
			$gateways['bacs']->description = 'Faça o pagamento diretamente em nossa conta bancária.';
			update_option( 'woocommerce_bacs_settings', $gateways['bacs']->settings );
		}

		if ( isset( $gateways['cod'] ) ) {
			$gateways['cod']->enabled     = 'yes';
			$gateways['cod']->title       = 'Pagamento na Entrega';
			$gateways['cod']->description = 'Pague quando receber o produto.';
			update_option( 'woocommerce_cod_settings', $gateways['cod']->settings );
		}

		if ( isset( $gateways['cheque'] ) ) {
			$gateways['cheque']->enabled     = 'yes';
			$gateways['cheque']->title       = 'Cheque';
			$gateways['cheque']->description = 'Envie um cheque para nosso endereço.';
			update_option( 'woocommerce_cheque_settings', $gateways['cheque']->settings );
		}
	}

	private function setupTaxes(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );
		update_option( 'woocommerce_tax_display_cart', 'incl' );

		$taxRates = array(
			array(
				'tax_rate_country'  => 'BR',
				'tax_rate_state'    => '',
				'tax_rate'          => '18.00',
				'tax_rate_name'     => 'ICMS',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '0',
			),
		);

		foreach ( $taxRates as $taxRate ) {
			\WC_Tax::_insert_tax_rate( $taxRate );
		}
	}

	private function setupShipping(): void {
		update_option( 'woocommerce_ship_to_countries', 'all' );
		update_option( 'woocommerce_allowed_countries', 'all' );

		$shippingZones = \WC_Shipping_Zones::get_zones();

		if ( empty( $shippingZones ) ) {
			$zone = new \WC_Shipping_Zone();
			$zone->set_zone_name( 'Brasil' );
			$zone->add_location( 'BR', 'country' );
			$zone->save();

			$instanceId = $zone->add_shipping_method( 'flat_rate' );

			if ( $instanceId ) {
				$methodSettings = get_option( "woocommerce_flat_rate_{$instanceId}_settings", array() );
				if ( empty( $methodSettings ) ) {
					$methodSettings = array(
						'title'      => 'Frete Fixo',
						'tax_status' => 'taxable',
						'cost'       => '15.00',
					);
					update_option( "woocommerce_flat_rate_{$instanceId}_settings", $methodSettings );
				}
			}
		}
	}

	private function completeSetup(): void {
		update_option(
			'woocommerce_onboarding_profile',
			array(
				'completed' => true,
				'skipped'   => false,
			)
		);

		update_option( 'woocommerce_onboarding_opt_in', 'yes' );
		update_option( 'woocommerce_task_list_complete', 'yes' );
		update_option( 'woocommerce_task_list_hidden', 'yes' );

		update_option( 'woocommerce_store_address', '' );
		update_option( 'woocommerce_store_address_2', '' );
		update_option( 'woocommerce_store_city', 'São Paulo' );
		update_option( 'woocommerce_default_country', 'BR:SP' );
		update_option( 'woocommerce_store_postcode', '01310-100' );

		update_option( 'woocommerce_currency', 'BRL' );
		update_option( 'woocommerce_currency_pos', 'left_space' );

		update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
		update_option( 'woocommerce_enable_checkout_login_reminder', 'yes' );
	}
}
