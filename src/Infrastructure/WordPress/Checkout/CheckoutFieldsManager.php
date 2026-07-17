<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Checkout;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;

final class CheckoutFieldsManager {

	/**
	 * Constants defined by known third-party plugins that customize WooCommerce checkout fields.
	 */
	private const KNOWN_CHECKOUT_FIELDS_PLUGIN_CONSTANTS = [
		'WC_BETTER_SHIPPING_CALCULATOR_FOR_BRAZIL_FILE', // Calculadora de Frete e Campos Checkout para o Brasil (Link Nacional)
		'CSBMW_PLUGIN_FILE',                              // Brazilian Market on WooCommerce (claudiosanches)
	];

	/**
	 * Id do campo nativo de checkout (Blocks). Precisa ser "namespace/nome".
	 */
	private const DOCUMENT_FIELD_ID = 'melhor-envio-cotacao/billing-document';

	private const NUMBER_FIELD_ID = 'melhor-envio-cotacao/address-number';

	private const NEIGHBORHOOD_FIELD_ID = 'melhor-envio-cotacao/neighborhood';

	public function register(): void {
		// Classic/shortcode checkout
		add_filter( 'woocommerce_checkout_fields',            [ $this, 'addDocumentFields' ] );
		add_filter( 'woocommerce_checkout_fields',            [ $this, 'addAddressFields' ] );
		add_action( 'woocommerce_checkout_process',           [ $this, 'validateFields' ] );
		add_action( 'woocommerce_checkout_update_order_meta', [ $this, 'saveFields' ] );
		add_action( 'wp_enqueue_scripts',                     [ $this, 'enqueueAssets' ] );

		// Blocks checkout - campos registrados via API nativa do WooCommerce (WC 8.9+), renderizados
		// pelo próprio React do Checkout Blocks, sem manipulação manual de DOM.
		add_action( 'woocommerce_init', [ $this, 'registerBlocksCheckoutField' ] );
		add_action( 'woocommerce_init', [ $this, 'registerBlocksAddressFields' ] );
		add_action( 'woocommerce_init', [ $this, 'ensureBlocksPhoneRequired' ] );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'saveFieldsFromBlocksRequest' ] );
		add_action( 'woocommerce_store_api_checkout_order_processed', [ $this, 'saveAddressFieldsFromBlocksRequest' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueBlocksAssets' ] );
	}

	/**
	 * No Checkout Blocks, o telefone obrigatório é controlado pela option
	 * 'woocommerce_checkout_phone_field' (não pelo filtro clássico 'woocommerce_checkout_fields'),
	 * e o padrão em checkouts novos é 'optional'.
	 */
	public function ensureBlocksPhoneRequired(): void {
		if ( $this->externalPluginActive() ) {
			return;
		}

		if ( get_option( 'woocommerce_checkout_phone_field' ) !== 'required' ) {
			update_option( 'woocommerce_checkout_phone_field', 'required' );
		}
	}

	private function externalPluginActive(): bool {
		foreach ( self::KNOWN_CHECKOUT_FIELDS_PLUGIN_CONSTANTS as $constant ) {
			if ( defined( $constant ) ) {
				return true;
			}
		}

		return false;
	}

	public function addDocumentFields( array $fields ): array {
		if ( $this->externalPluginActive() ) {
			return $fields;
		}

		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['required'] = true;
		}

		// Fora da submissão o $_POST ainda não tem o tipo escolhido - assume '1' (CPF, opção
		// padrão do select) pra decidir qual dos dois já nasce marcado como obrigatório. Na
		// submissão real (validate_posted_data), esse filtro roda de novo já com o tipo
		// escolhido no $_POST, então o campo certo (e só ele) é o exigido pelo WooCommerce.
		$personType = sanitize_text_field( wp_unslash( $_POST['billing_persontype'] ?? '1' ) );

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
			'required'    => $personType !== '2',
			'class'       => [ 'form-row-wide', 'me-doc-cpf' ],
			'placeholder' => '000.000.000-00',
			'maxlength'   => 14,
			'priority'    => 32,
		];

		$fields['billing']['billing_cnpj'] = [
			'label'       => __( 'CNPJ', 'melhor-envio-cotacao' ),
			'type'        => 'text',
			'required'    => $personType === '2',
			'class'       => [ 'form-row-wide', 'me-doc-cnpj' ],
			'placeholder' => 'AB.CDE.FGH/IJKL-12',
			'maxlength'   => 18,
			'priority'    => 33,
		];

		return $fields;
	}

	/**
	 * Número e bairro são exigidos pelos Correios/transportadoras pra gerar a etiqueta -
	 * legacy/Services/BuyerService.php já lê '_shipping_number'/'_shipping_neighborhood'
	 * pra montar o destinatário do envio.
	 */
	public function addAddressFields( array $fields ): array {
		if ( $this->externalPluginActive() ) {
			return $fields;
		}

		foreach ( [ 'billing', 'shipping' ] as $group ) {
			$fields[ $group ][ $group . '_number' ] = [
				'label'       => __( 'Número', 'melhor-envio-cotacao' ),
				'type'        => 'text',
				'required'    => true,
				'class'       => [ 'form-row-wide' ],
				'placeholder' => __( 'Ex: 123', 'melhor-envio-cotacao' ),
				'priority'    => 55,
			];

			$fields[ $group ][ $group . '_neighborhood' ] = [
				'label'       => __( 'Bairro', 'melhor-envio-cotacao' ),
				'type'        => 'text',
				'required'    => true,
				'class'       => [ 'form-row-wide' ],
				'placeholder' => __( 'Digite o nome do bairro', 'melhor-envio-cotacao' ),
				'priority'    => 69,
			];
		}

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

		foreach ( [ 'number', 'neighborhood' ] as $suffix ) {
			$billingValue  = sanitize_text_field( wp_unslash( $_POST[ 'billing_' . $suffix ] ?? '' ) );
			$shippingValue = sanitize_text_field( wp_unslash( $_POST[ 'shipping_' . $suffix ] ?? '' ) ) ?: $billingValue;

			update_post_meta( $orderId, '_billing_' . $suffix, $billingValue );
			update_post_meta( $orderId, '_shipping_' . $suffix, $shippingValue );
		}
	}

	public function registerBlocksCheckoutField(): void {
		if ( $this->externalPluginActive() ) {
			return;
		}
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		woocommerce_register_additional_checkout_field( [
			'id'                => self::DOCUMENT_FIELD_ID,
			'label'             => __( 'CPF / CNPJ', 'melhor-envio-cotacao' ),
			'location'          => 'address',
			'type'              => 'text',
			'required'          => true,
			'attributes'        => [
				'autocomplete'   => 'off',
				'autocapitalize' => 'characters',
				'maxLength'      => 18,
			],
			'sanitize_callback' => function ( $value ) {
				return strtoupper( preg_replace( '/[^0-9A-Za-z]/', '', (string) $value ) );
			},
			'validate_callback' => function ( $value ) {
				return $this->validateDocumentField( (string) $value );
			},
		] );
	}

	public function registerBlocksAddressFields(): void {
		if ( $this->externalPluginActive() ) {
			return;
		}
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}

		woocommerce_register_additional_checkout_field( [
			'id'       => self::NUMBER_FIELD_ID,
			'label'    => __( 'Número', 'melhor-envio-cotacao' ),
			'location' => 'address',
			'type'     => 'text',
			'required' => true,
		] );

		woocommerce_register_additional_checkout_field( [
			'id'       => self::NEIGHBORHOOD_FIELD_ID,
			'label'    => __( 'Bairro', 'melhor-envio-cotacao' ),
			'location' => 'address',
			'type'     => 'text',
			'required' => true,
		] );
	}

	private function validateDocumentField( string $value ): ?\WP_Error {
		if ( $value === '' ) {
			return new \WP_Error( 'melhor_envio_document_required', __( 'Informe um CPF ou CNPJ.', 'melhor-envio-cotacao' ) );
		}

		if ( strlen( $value ) === 11 ) {
			return $this->isValidCpf( $value )
				? null
				: new \WP_Error( 'melhor_envio_document_invalid', __( 'CPF inválido.', 'melhor-envio-cotacao' ) );
		}

		if ( strlen( $value ) === 14 ) {
			return $this->isValidCnpj( $value )
				? null
				: new \WP_Error( 'melhor_envio_document_invalid', __( 'CNPJ inválido.', 'melhor-envio-cotacao' ) );
		}

		return new \WP_Error(
			'melhor_envio_document_invalid',
			__( 'Informe um CPF (11 dígitos) ou CNPJ (14 caracteres).', 'melhor-envio-cotacao' )
		);
	}

	public function saveFieldsFromBlocksRequest( \WC_Order $order ): void {
		if ( $this->externalPluginActive() ) {
			return;
		}

		// Campo é 'address', então pode ter sido preenchido no form de billing e/ou de
		// shipping (quando "usar mesmo endereço" está desmarcado) - billing tem prioridade
		// por ser o documento associado à cobrança/nota fiscal.
		$billingPrefix  = class_exists( CheckoutFields::class ) ? CheckoutFields::BILLING_FIELDS_PREFIX : '_wc_billing/';
		$shippingPrefix = class_exists( CheckoutFields::class ) ? CheckoutFields::SHIPPING_FIELDS_PREFIX : '_wc_shipping/';

		$document = (string) $order->get_meta( $billingPrefix . self::DOCUMENT_FIELD_ID );

		if ( $document === '' ) {
			$document = (string) $order->get_meta( $shippingPrefix . self::DOCUMENT_FIELD_ID );
		}

		if ( $document === '' ) {
			return;
		}

		$isCnpj = strlen( $document ) > 11 || (bool) preg_match( '/[A-Z]/', $document );

		$order->update_meta_data( '_billing_persontype', $isCnpj ? '2' : '1' );
		$order->update_meta_data( '_billing_cpf', $isCnpj ? '' : $document );
		$order->update_meta_data( '_billing_cnpj', $isCnpj ? $document : '' );
		$order->save();
	}

	public function saveAddressFieldsFromBlocksRequest( \WC_Order $order ): void {
		if ( $this->externalPluginActive() ) {
			return;
		}

		$billingPrefix  = class_exists( CheckoutFields::class ) ? CheckoutFields::BILLING_FIELDS_PREFIX : '_wc_billing/';
		$shippingPrefix = class_exists( CheckoutFields::class ) ? CheckoutFields::SHIPPING_FIELDS_PREFIX : '_wc_shipping/';

		foreach ( [ self::NUMBER_FIELD_ID => 'number', self::NEIGHBORHOOD_FIELD_ID => 'neighborhood' ] as $fieldId => $suffix ) {
			$billingValue  = (string) $order->get_meta( $billingPrefix . $fieldId );
			$shippingValue = (string) $order->get_meta( $shippingPrefix . $fieldId ) ?: $billingValue;

			$order->update_meta_data( '_billing_' . $suffix, $billingValue );
			$order->update_meta_data( '_shipping_' . $suffix, $shippingValue );
		}

		$order->save();
	}

	public function enqueueBlocksAssets(): void {
		// Sem checagem de has_block(): em temas block/FSE o checkout pode vir de um
		// template, não do conteúdo do post, e has_block() dá falso negativo nesse caso.
		// is_checkout() já é suficiente - no clássico os seletores CSS/JS simplesmente não
		// casam com nada (classes só existem no DOM do Checkout Blocks).
		if ( $this->externalPluginActive() || ! is_checkout() ) {
			return;
		}

		wp_enqueue_script(
			'me-checkout-blocks',
			MELHORENVIO_URL . '/assets/js/me-checkout-blocks.js',
			[],
			MELHORENVIO_VERSION,
			true
		);

		wp_enqueue_style(
			'me-checkout-blocks',
			MELHORENVIO_URL . '/assets/css/me-checkout-blocks.css',
			[],
			MELHORENVIO_VERSION
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
    function meSetRequired($input, isRequired) {
        var $row   = $input.closest('.form-row');
        var $label = $row.find('label[for="' + $input.attr('id') + '"]');

        $row.toggleClass('validate-required', isRequired);
        $label.toggleClass('required_field', isRequired);
        $input.attr('aria-required', isRequired ? 'true' : 'false');

        $label.find('.required, .optional').remove();
        $label.append(
            isRequired
                ? '&nbsp;<span class="required" aria-hidden="true">*</span>'
                : '&nbsp;<span class="optional">(opcional)</span>'
        );
    }
    function meToggleDocs() {
        var t       = $('#billing_persontype').val();
        var isCnpj  = t === '2';
        var $cpf    = $('#billing_cpf');
        var $cnpj   = $('#billing_cnpj');

        $cpf.closest('.form-row').toggle(!isCnpj);
        $cnpj.closest('.form-row').toggle(isCnpj);

        meSetRequired($cpf, !isCnpj);
        meSetRequired($cnpj, isCnpj);
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
