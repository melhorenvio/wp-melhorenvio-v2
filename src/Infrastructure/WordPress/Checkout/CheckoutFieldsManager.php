<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Checkout;

final class CheckoutFieldsManager {

	public function register(): void {
		// Classic/shortcode checkout
		add_filter( 'woocommerce_checkout_fields',            [ $this, 'addDocumentFields' ] );
		add_action( 'woocommerce_checkout_process',           [ $this, 'validateFields' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'saveFields' ] );
		add_action( 'wp_enqueue_scripts',                     [ $this, 'enqueueAssets' ] );

		// Blocks checkout
		add_action( 'woocommerce_init', [ $this, 'registerStoreApiSchema' ] );
		add_action(
			'woocommerce_store_api_checkout_update_order_from_request',
			[ $this, 'saveFieldsFromBlocksRequest' ],
			10,
			2
		);
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueBlocksAssets' ] );
	}

	private function externalPluginActive(): bool {
		$option = get_option( 'woo_better_calc_person_type_select', 'none' );
		return ! empty( $option ) && $option !== 'none';
	}

	public function addDocumentFields( array $fields ): array {
		if ( $this->externalPluginActive() ) {
			return $fields;
		}

		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['required'] = true;
		}

		$fields['billing']['billing_persontype'] = [
			'label'    => __( 'Tipo de pessoa', 'melhor-envio-cotacao' ),
			'type'     => 'select',
			'options'  => [
				'1' => __( 'Pessoa física (CPF)', 'melhor-envio-cotacao' ),
				'2' => __( 'Pessoa jurídica (CNPJ)', 'melhor-envio-cotacao' ),
			],
			'required' => true,
			'class'    => [ 'form-row-wide' ],
			'priority' => 31,
		];

		$fields['billing']['billing_cpf'] = [
			'label'       => __( 'CPF', 'melhor-envio-cotacao' ),
			'type'        => 'text',
			'required'    => false,
			'class'       => [ 'form-row-wide', 'me-doc-cpf' ],
			'placeholder' => '000.000.000-00',
			'maxlength'   => 14,
			'priority'    => 32,
		];

		$fields['billing']['billing_cnpj'] = [
			'label'       => __( 'CNPJ', 'melhor-envio-cotacao' ),
			'type'        => 'text',
			'required'    => false,
			'class'       => [ 'form-row-wide', 'me-doc-cnpj' ],
			'placeholder' => 'AB.CDE.FGH/IJKL-12',
			'maxlength'   => 18,
			'priority'    => 33,
		];

		return $fields;
	}

	public function validateFields(): void {
		if ( $this->externalPluginActive() ) {
			return;
		}

		$personType = sanitize_text_field( wp_unslash( $_POST['billing_persontype'] ?? '1' ) );
		$cpf        = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['billing_cpf'] ?? '' ) ) );
		$cnpj       = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', sanitize_text_field( wp_unslash( $_POST['billing_cnpj'] ?? '' ) ) ) );

		if ( $personType === '1' && ! $this->isValidCpf( $cpf ) ) {
			wc_add_notice( __( 'CPF inválido.', 'melhor-envio-cotacao' ), 'error' );
		}

		if ( $personType === '2' && ! $this->isValidCnpj( $cnpj ) ) {
			wc_add_notice( __( 'CNPJ inválido.', 'melhor-envio-cotacao' ), 'error' );
		}

		$phone = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['billing_phone'] ?? '' ) ) );
		if ( strlen( $phone ) < 10 ) {
			wc_add_notice( __( 'Telefone inválido. Informe DDD + número.', 'melhor-envio-cotacao' ), 'error' );
		}

		$postcode = preg_replace( '/\D/', '', sanitize_text_field( wp_unslash( $_POST['billing_postcode'] ?? '' ) ) );
		if ( strlen( $postcode ) !== 8 ) {
			wc_add_notice( __( 'CEP inválido. Informe 8 dígitos.', 'melhor-envio-cotacao' ), 'error' );
		}
	}

	private function isValidCpf( string $cpf ): bool {
		if ( strlen( $cpf ) !== 11 || preg_match( '/^(\d)\1{10}$/', $cpf ) ) {
			return false;
		}
		for ( $t = 9; $t < 11; $t++ ) {
			$sum = 0;
			for ( $i = 0; $i < $t; $i++ ) {
				$sum += (int) $cpf[ $i ] * ( $t + 1 - $i );
			}
			$d = ( ( 10 * $sum ) % 11 ) % 10;
			if ( (int) $cpf[ $t ] !== $d ) {
				return false;
			}
		}
		return true;
	}

	// CNPJ alfanumérico (Receita Federal, jul/2026): posições 1-12 = [A-Z0-9], 13-14 = [0-9]
	// Mapeamento: ord($c) - 48 (ex: '0'→0, 'A'→17, 'B'→18...)
	private function isValidCnpj( string $cnpj ): bool {
		if ( strlen( $cnpj ) !== 14 ) {
			return false;
		}
		if ( ! preg_match( '/^[A-Z0-9]{12}[0-9]{2}$/', $cnpj ) ) {
			return false;
		}
		if ( preg_match( '/^(.)\1{13}$/', $cnpj ) ) {
			return false;
		}

		$val = static function ( string $c ): int {
			return ord( $c ) - 48;
		};

		$weights1 = [ 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];
		$weights2 = [ 6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2 ];

		$check = static function ( string $cnpj, array $weights, int $len ) use ( $val ): int {
			$sum = 0;
			for ( $i = 0; $i < $len; $i++ ) {
				$sum += $val( $cnpj[ $i ] ) * $weights[ $i ];
			}
			$r = $sum % 11;
			return $r < 2 ? 0 : 11 - $r;
		};

		return (int) $cnpj[12] === $check( $cnpj, $weights1, 12 )
			&& (int) $cnpj[13] === $check( $cnpj, $weights2, 13 );
	}

	public function saveFields( int $orderId ): void {
		if ( $this->externalPluginActive() ) {
			return;
		}

		foreach ( [ 'billing_persontype', 'billing_cpf', 'billing_cnpj' ] as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				update_post_meta(
					$orderId,
					'_' . $field,
					sanitize_text_field( wp_unslash( $_POST[ $field ] ) )
				);
			}
		}
	}

	public function registerStoreApiSchema(): void {
		if ( $this->externalPluginActive() ) {
			return;
		}
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}
		woocommerce_store_api_register_endpoint_data( [
			'endpoint'        => 'checkout',
			'namespace'       => 'melhor_envio_person_type',
			'schema_callback' => fn() => [
				'billing_persontype' => [ 'type' => 'string', 'readonly' => true ],
				'billing_cpf'        => [ 'type' => 'string', 'readonly' => true ],
				'billing_cnpj'       => [ 'type' => 'string', 'readonly' => true ],
			],
			'data_callback'   => fn() => [
				'billing_persontype' => '',
				'billing_cpf'        => '',
				'billing_cnpj'       => '',
			],
		] );
	}

	public function saveFieldsFromBlocksRequest( \WC_Order $order, \WP_REST_Request $request ): void {
		if ( $this->externalPluginActive() ) {
			return;
		}
		$extensions = $request->get_param( 'extensions' ) ?? [];
		$raw        = $extensions['melhor_envio_person_type'] ?? [];
		$data       = is_array( $raw ) ? $raw : [];

		$persontype = sanitize_text_field( $data['billing_persontype'] ?? '' );
		$cpf        = sanitize_text_field( $data['billing_cpf']        ?? '' );
		$cnpj       = sanitize_text_field( $data['billing_cnpj']       ?? '' );

		if ( empty( $persontype ) && isset( $_POST['billing_persontype'] ) ) {
			$persontype = sanitize_text_field( wp_unslash( $_POST['billing_persontype'] ) );
		}

		if ( $persontype ) {
			$order->update_meta_data( '_billing_persontype', (int) $persontype );
		}
		if ( $cpf ) {
			$order->update_meta_data( '_billing_cpf', $cpf );
		}
		if ( $cnpj ) {
			$order->update_meta_data( '_billing_cnpj', $cnpj );
		}
		$order->save();
	}

	public function enqueueBlocksAssets(): void {
		if ( $this->externalPluginActive() || ! is_checkout() ) {
			return;
		}
		global $post;
		if ( ! function_exists( 'has_block' ) || ! has_block( 'woocommerce/checkout', $post ) ) {
			return;
		}
		wp_enqueue_script(
			'me-checkout-blocks',
			MELHORENVIO_URL . '/assets/js/me-checkout-blocks.js',
			[],
			MELHORENVIO_VERSION,
			true
		);
	}

	public function enqueueAssets(): void {
		if ( ! is_checkout() || $this->externalPluginActive() ) {
			return;
		}
		wp_add_inline_script( 'woocommerce', $this->getInlineScript() );
	}

	private function getInlineScript(): string {
		return <<<'JS'
(function($){
    function meToggleDocs() {
        var t = $('#billing_persontype').val();
        $('.me-doc-cpf').closest('.form-row').toggle(t !== '2');
        $('.me-doc-cnpj').closest('.form-row').toggle(t === '2');
    }
    function meMaskCpf($el) {
        $el.on('input', function(){
            var v = $(this).val().replace(/\D/g,'').slice(0,11);
            if(v.length>9) v=v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6,9)+'-'+v.slice(9);
            else if(v.length>6) v=v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6);
            else if(v.length>3) v=v.slice(0,3)+'.'+v.slice(3);
            $(this).val(v);
        });
    }
    function meMaskCnpj($el) {
        $el.on('input', function(){
            var raw = $(this).val().toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,14);
            var r = '';
            if(raw.length>0) r = raw.slice(0,2);
            if(raw.length>2) r += '.'+raw.slice(2,5);
            if(raw.length>5) r += '.'+raw.slice(5,8);
            if(raw.length>8) r += '/'+raw.slice(8,12);
            if(raw.length>12) r += '-'+raw.slice(12,14);
            $(this).val(r);
        });
    }
    function meInit() {
        meToggleDocs();
        meMaskCpf($('#billing_cpf'));
        meMaskCnpj($('#billing_cnpj'));
        $(document).off('change.me_doc','#billing_persontype').on('change.me_doc','#billing_persontype', meToggleDocs);
    }
    $(document).on('ready updated_checkout', meInit);
})(jQuery);
JS;
	}
}
