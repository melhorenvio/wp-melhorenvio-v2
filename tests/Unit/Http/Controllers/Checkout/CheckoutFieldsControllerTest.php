<?php

declare(strict_types=1);

namespace MelhorEnvio\Tests\Unit\Http\Controllers\Checkout;

use Brain\Monkey\Functions;
use MelhorEnvio\Http\Controllers\Checkout\CheckoutFieldsController;
use MelhorEnvio\Tests\TestCase;
use Mockery;

final class CheckoutFieldsControllerTest extends TestCase {

	private const VALID_CPF             = '52998224725';
	private const VALID_CNPJ            = '11222333000181';
	private const VALID_ALPHA_CNPJ      = '12ABC34501DE35';
	private const DOCUMENT_FIELD_ID     = 'melhor-envio-cotacao/billing-document';
	private const NUMBER_FIELD_ID       = 'melhor-envio-cotacao/address-number';
	private const NEIGHBORHOOD_FIELD_ID = 'melhor-envio-cotacao/neighborhood';

	private CheckoutFieldsController $controller;

	/** @var array<int, string> */
	private array $notices = array();

	/** @var array<string, mixed> */
	private array $savedMeta = array();

	/** @var array<int, array<string, mixed>> */
	private array $registeredFields = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\stubTranslationFunctions();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();

		$this->controller = new CheckoutFieldsController();
	}

	public function test_register_hooks_classic_and_blocks_checkout(): void {
		$this->controller->register();

		self::assertNotFalse( has_filter( 'woocommerce_checkout_fields', array( $this->controller, 'addDocumentFields' ) ) );
		self::assertNotFalse( has_filter( 'woocommerce_checkout_fields', array( $this->controller, 'addAddressFields' ) ) );
		self::assertNotFalse( has_action( 'woocommerce_checkout_process', array( $this->controller, 'validateFields' ) ) );
		self::assertNotFalse( has_action( 'woocommerce_checkout_create_order', array( $this->controller, 'saveFields' ) ) );
		self::assertNotFalse( has_action( 'wp_enqueue_scripts', array( $this->controller, 'enqueueAssets' ) ) );
		self::assertNotFalse( has_action( 'woocommerce_init', array( $this->controller, 'registerBlocksCheckoutField' ) ) );
		self::assertNotFalse( has_action( 'woocommerce_init', array( $this->controller, 'registerBlocksAddressFields' ) ) );
		self::assertNotFalse( has_action( 'woocommerce_init', array( $this->controller, 'ensureBlocksPhoneRequired' ) ) );
		self::assertNotFalse( has_action( 'woocommerce_store_api_checkout_order_processed', array( $this->controller, 'saveFieldsFromBlocksRequest' ) ) );
		self::assertNotFalse( has_action( 'woocommerce_store_api_checkout_order_processed', array( $this->controller, 'saveAddressFieldsFromBlocksRequest' ) ) );
		self::assertNotFalse( has_action( 'wp_enqueue_scripts', array( $this->controller, 'enqueueBlocksAssets' ) ) );
	}

	/*
	 * ensureBlocksPhoneRequired
	 */

	public function test_ensure_blocks_phone_required_forces_option_to_required(): void {
		Functions\when( 'get_option' )->justReturn( 'optional' );
		Functions\expect( 'update_option' )->once()->with( 'woocommerce_checkout_phone_field', 'required' );

		$this->controller->ensureBlocksPhoneRequired();
	}

	public function test_ensure_blocks_phone_required_skips_update_when_already_required(): void {
		Functions\expect( 'get_option' )->with( 'woocommerce_checkout_phone_field' )->andReturn( 'required' );
		Functions\expect( 'update_option' )->never();

		$this->controller->ensureBlocksPhoneRequired();
	}

	/*
	 * addDocumentFields
	 */

	public function test_add_document_fields_makes_existing_phone_required(): void {
		$fields = $this->controller->addDocumentFields(
			array( 'billing' => array( 'billing_phone' => array( 'required' => false, 'label' => 'Telefone' ) ) )
		);

		self::assertTrue( $fields['billing']['billing_phone']['required'] );
		self::assertSame( 'Telefone', $fields['billing']['billing_phone']['label'] );
	}

	public function test_add_document_fields_does_not_create_phone_when_absent(): void {
		$fields = $this->controller->addDocumentFields( array( 'billing' => array() ) );

		self::assertArrayNotHasKey( 'billing_phone', $fields['billing'] );
	}

	public function test_add_document_fields_registers_person_type_select(): void {
		$fields = $this->controller->addDocumentFields( array( 'billing' => array() ) );

		$personType = $fields['billing']['billing_persontype'];
		self::assertSame( 'select', $personType['type'] );
		self::assertSame( array( '1', '2' ), array_map( 'strval', array_keys( $personType['options'] ) ) );
		self::assertTrue( $personType['required'] );
		self::assertSame( 31, $personType['priority'] );
	}

	public function test_add_document_fields_requires_cpf_by_default(): void {
		$fields = $this->controller->addDocumentFields( array( 'billing' => array() ) );

		self::assertTrue( $fields['billing']['billing_cpf']['required'] );
		self::assertFalse( $fields['billing']['billing_cnpj']['required'] );
		self::assertSame( 14, $fields['billing']['billing_cpf']['maxlength'] );
		self::assertSame( 18, $fields['billing']['billing_cnpj']['maxlength'] );
		self::assertContains( 'me-doc-cpf', $fields['billing']['billing_cpf']['class'] );
		self::assertContains( 'me-doc-cnpj', $fields['billing']['billing_cnpj']['class'] );
	}

	public function test_add_document_fields_requires_cnpj_when_legal_person_is_posted(): void {
		$_POST['billing_persontype'] = '2';

		$fields = $this->controller->addDocumentFields( array( 'billing' => array() ) );

		self::assertFalse( $fields['billing']['billing_cpf']['required'] );
		self::assertTrue( $fields['billing']['billing_cnpj']['required'] );
	}

	public function test_add_document_fields_keeps_unrelated_fields(): void {
		$fields = $this->controller->addDocumentFields(
			array(
				'billing'  => array( 'billing_first_name' => array( 'label' => 'Nome' ) ),
				'shipping' => array( 'shipping_city' => array( 'label' => 'Cidade' ) ),
			)
		);

		self::assertSame( 'Nome', $fields['billing']['billing_first_name']['label'] );
		self::assertSame( array( 'shipping_city' => array( 'label' => 'Cidade' ) ), $fields['shipping'] );
	}

	/*
	 * addAddressFields
	 */

	public function test_add_address_fields_adds_required_number_and_neighborhood_to_both_groups(): void {
		$fields = $this->controller->addAddressFields(
			array(
				'billing'  => array( 'billing_city' => array( 'label' => 'Cidade' ) ),
				'shipping' => array(),
			)
		);

		foreach ( array( 'billing', 'shipping' ) as $group ) {
			self::assertTrue( $fields[ $group ][ $group . '_number' ]['required'] );
			self::assertSame( 'text', $fields[ $group ][ $group . '_number' ]['type'] );
			self::assertSame( 55, $fields[ $group ][ $group . '_number' ]['priority'] );
			self::assertTrue( $fields[ $group ][ $group . '_neighborhood' ]['required'] );
			self::assertSame( 69, $fields[ $group ][ $group . '_neighborhood' ]['priority'] );
		}

		self::assertSame( 'Cidade', $fields['billing']['billing_city']['label'] );
	}

	/*
	 * validateFields
	 */

	public function test_validate_fields_accepts_valid_cpf_submission(): void {
		$this->givenValidCheckoutPost( array( 'billing_cpf' => '529.982.247-25' ) );
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array(), $this->notices );
	}

	public function test_validate_fields_defaults_to_cpf_when_person_type_is_missing(): void {
		$this->givenValidCheckoutPost( array( 'billing_cpf' => '111.111.111-11' ) );
		unset( $_POST['billing_persontype'] );
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array( 'CPF inválido.' ), $this->notices );
	}

	/**
	 * @dataProvider invalidCpfs
	 */
	public function test_validate_fields_rejects_invalid_cpf( string $cpf ): void {
		$this->givenValidCheckoutPost( array( 'billing_cpf' => $cpf ) );
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array( 'CPF inválido.' ), $this->notices );
	}

	public function invalidCpfs(): array {
		return array(
			'empty'              => array( '' ),
			'wrong check digits' => array( '529.982.247-24' ),
			'repeated digits'    => array( '000.000.000-00' ),
			'too short'          => array( '5299822472' ),
			'too long'           => array( '529982247250' ),
		);
	}

	/**
	 * @dataProvider validCnpjs
	 */
	public function test_validate_fields_accepts_valid_cnpj( string $cnpj ): void {
		$this->givenValidCheckoutPost(
			array(
				'billing_persontype' => '2',
				'billing_cpf'        => '',
				'billing_cnpj'       => $cnpj,
			)
		);
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array(), $this->notices );
	}

	public function validCnpjs(): array {
		return array(
			'numeric formatted'          => array( '11.222.333/0001-81' ),
			'numeric raw'                => array( self::VALID_CNPJ ),
			'alphanumeric formatted'     => array( '12.ABC.345/01DE-35' ),
			'alphanumeric lowercase raw' => array( '12abc34501de35' ),
		);
	}

	/**
	 * @dataProvider invalidCnpjs
	 */
	public function test_validate_fields_rejects_invalid_cnpj( string $cnpj ): void {
		$this->givenValidCheckoutPost(
			array(
				'billing_persontype' => '2',
				'billing_cnpj'       => $cnpj,
			)
		);
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array( 'CNPJ inválido.' ), $this->notices );
	}

	public function invalidCnpjs(): array {
		return array(
			'empty'                       => array( '' ),
			'wrong check digits'          => array( '11.222.333/0001-82' ),
			'repeated digits'             => array( '00.000.000/0000-00' ),
			'too short'                   => array( '1122233300018' ),
			'letters in check digits'     => array( '12.ABC.345/01DE-3A' ),
			'alphanumeric wrong checksum' => array( '12.ABC.345/01DE-36' ),
		);
	}

	public function test_validate_fields_ignores_cpf_when_person_type_is_cnpj(): void {
		$this->givenValidCheckoutPost(
			array(
				'billing_persontype' => '2',
				'billing_cpf'        => 'garbage',
				'billing_cnpj'       => self::VALID_CNPJ,
			)
		);
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array(), $this->notices );
	}

	public function test_validate_fields_ignores_cnpj_when_person_type_is_cpf(): void {
		$this->givenValidCheckoutPost( array( 'billing_cnpj' => 'garbage' ) );
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array(), $this->notices );
	}

	/**
	 * @dataProvider invalidPhones
	 */
	public function test_validate_fields_rejects_phone_without_area_code( string $phone ): void {
		$this->givenValidCheckoutPost( array( 'billing_phone' => $phone ) );
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array( 'Telefone inválido. Informe DDD + número.' ), $this->notices );
	}

	public function invalidPhones(): array {
		return array(
			'empty'         => array( '' ),
			'no area code'  => array( '9999-8888' ),
			'only letters'  => array( 'telefone' ),
			'nine digits'   => array( '(1) 9999-8888' ),
		);
	}

	public function test_validate_fields_accepts_formatted_landline_phone(): void {
		$this->givenValidCheckoutPost( array( 'billing_phone' => '(11) 3333-4444' ) );
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array(), $this->notices );
	}

	/**
	 * @dataProvider invalidPostcodes
	 */
	public function test_validate_fields_rejects_postcode_without_eight_digits( string $postcode ): void {
		$this->givenValidCheckoutPost( array( 'billing_postcode' => $postcode ) );
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame( array( 'CEP inválido. Informe 8 dígitos.' ), $this->notices );
	}

	public function invalidPostcodes(): array {
		return array(
			'empty'     => array( '' ),
			'too short' => array( '01310-10' ),
			'too long'  => array( '01310-1000' ),
		);
	}

	public function test_validate_fields_reports_every_invalid_field(): void {
		$_POST = array();
		$this->collectNotices();

		$this->controller->validateFields();

		self::assertSame(
			array(
				'CPF inválido.',
				'Telefone inválido. Informe DDD + número.',
				'CEP inválido. Informe 8 dígitos.',
			),
			$this->notices
		);
	}

	/*
	 * saveFields
	 */

	public function test_save_fields_persists_document_and_address_meta(): void {
		$_POST = array(
			'billing_persontype'    => '1',
			'billing_cpf'           => '529.982.247-25',
			'billing_cnpj'          => '',
			'billing_number'        => '100',
			'billing_neighborhood'  => 'Centro',
			'shipping_number'       => '200',
			'shipping_neighborhood' => 'Bela Vista',
		);
		$this->controller->saveFields( $this->orderCollectingMeta() );

		self::assertSame(
			array(
				'_billing_persontype'    => '1',
				'_billing_cpf'           => '529.982.247-25',
				'_billing_cnpj'          => '',
				'_billing_number'        => '100',
				'_shipping_number'       => '200',
				'_billing_neighborhood'  => 'Centro',
				'_shipping_neighborhood' => 'Bela Vista',
			),
			$this->savedMeta
		);
	}

	public function test_save_fields_copies_billing_address_into_empty_shipping_fields(): void {
		$_POST = array(
			'billing_number'        => '100',
			'billing_neighborhood'  => 'Centro',
			'shipping_number'       => '',
		);
		$this->controller->saveFields( $this->orderCollectingMeta() );

		self::assertSame( '100', $this->savedMeta['_shipping_number'] );
		self::assertSame( 'Centro', $this->savedMeta['_shipping_neighborhood'] );
	}

	public function test_save_fields_skips_document_fields_not_posted(): void {
		$_POST = array();
		$this->controller->saveFields( $this->orderCollectingMeta() );

		self::assertSame(
			array(
				'_billing_number'        => '',
				'_shipping_number'       => '',
				'_billing_neighborhood'  => '',
				'_shipping_neighborhood' => '',
			),
			$this->savedMeta
		);
	}

	/*
	 * registerBlocksCheckoutField / document validation
	 */

	public function test_register_blocks_checkout_field_registers_required_document_field(): void {
		$field = $this->registerDocumentField();

		self::assertSame( self::DOCUMENT_FIELD_ID, $field['id'] );
		self::assertSame( 'address', $field['location'] );
		self::assertSame( 'text', $field['type'] );
		self::assertTrue( $field['required'] );
		self::assertSame( 18, $field['attributes']['maxLength'] );
	}

	/**
	 * @dataProvider documentsToSanitize
	 */
	public function test_document_field_sanitizer_strips_mask_and_uppercases( $raw, string $expected ): void {
		$field = $this->registerDocumentField();

		self::assertSame( $expected, $field['sanitize_callback']( $raw ) );
	}

	public function documentsToSanitize(): array {
		return array(
			'masked cpf'          => array( '529.982.247-25', self::VALID_CPF ),
			'masked lowercase'    => array( '12.abc.345/01de-35', self::VALID_ALPHA_CNPJ ),
			'whitespace'          => array( ' 529 982 247 25 ', self::VALID_CPF ),
			'null'                => array( null, '' ),
		);
	}

	/**
	 * @dataProvider validDocuments
	 */
	public function test_document_field_validator_accepts_valid_documents( string $document ): void {
		$field = $this->registerDocumentField();

		self::assertNull( $field['validate_callback']( $document ) );
	}

	public function validDocuments(): array {
		return array(
			'cpf'               => array( self::VALID_CPF ),
			'numeric cnpj'      => array( self::VALID_CNPJ ),
			'alphanumeric cnpj' => array( self::VALID_ALPHA_CNPJ ),
		);
	}

	/**
	 * @dataProvider invalidDocuments
	 */
	public function test_document_field_validator_rejects_invalid_documents( string $document, string $code, string $message ): void {
		$field = $this->registerDocumentField();

		$error = $field['validate_callback']( $document );

		self::assertInstanceOf( \WP_Error::class, $error );
		self::assertSame( $code, $error->get_error_code() );
		self::assertSame( $message, $error->get_error_message() );
	}

	public function invalidDocuments(): array {
		return array(
			'empty'             => array( '', 'melhor_envio_document_required', 'Informe um CPF ou CNPJ.' ),
			'invalid cpf'       => array( '52998224724', 'melhor_envio_document_invalid', 'CPF inválido.' ),
			'repeated cpf'      => array( '11111111111', 'melhor_envio_document_invalid', 'CPF inválido.' ),
			'invalid cnpj'      => array( '11222333000182', 'melhor_envio_document_invalid', 'CNPJ inválido.' ),
			'repeated cnpj'     => array( '11111111111111', 'melhor_envio_document_invalid', 'CNPJ inválido.' ),
			'wrong length'      => array( '123456789', 'melhor_envio_document_invalid', 'Informe um CPF (11 dígitos) ou CNPJ (14 caracteres).' ),
			'between lengths'   => array( '1234567890123', 'melhor_envio_document_invalid', 'Informe um CPF (11 dígitos) ou CNPJ (14 caracteres).' ),
		);
	}

	public function test_document_field_validator_rejects_cpf_containing_letters(): void {
		$field = $this->registerDocumentField();

		$error = $field['validate_callback']( 'O1234567890' );

		self::assertInstanceOf( \WP_Error::class, $error );
		self::assertSame( 'CPF inválido.', $error->get_error_message() );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_register_blocks_fields_bail_out_when_woocommerce_api_is_missing(): void {
		$this->controller->registerBlocksCheckoutField();
		$this->controller->registerBlocksAddressFields();

		self::assertFalse( function_exists( 'woocommerce_register_additional_checkout_field' ) );
	}

	public function test_register_blocks_address_fields_registers_number_and_neighborhood(): void {
		$this->collectRegisteredFields();

		$this->controller->registerBlocksAddressFields();

		self::assertSame(
			array( self::NUMBER_FIELD_ID, self::NEIGHBORHOOD_FIELD_ID ),
			array_column( $this->registeredFields, 'id' )
		);

		foreach ( $this->registeredFields as $field ) {
			self::assertSame( 'address', $field['location'] );
			self::assertSame( 'text', $field['type'] );
			self::assertTrue( $field['required'] );
		}
	}

	/*
	 * saveFieldsFromBlocksRequest
	 */

	public function test_save_fields_from_blocks_request_stores_billing_cpf(): void {
		$order = $this->mockOrder( array( '_wc_billing/' . self::DOCUMENT_FIELD_ID => self::VALID_CPF ) );
		$order->expects( 'save' )->once();

		$this->controller->saveFieldsFromBlocksRequest( $order );

		self::assertSame(
			array(
				'_billing_persontype' => '1',
				'_billing_cpf'        => self::VALID_CPF,
				'_billing_cnpj'       => '',
			),
			$this->savedMeta
		);
	}

	/**
	 * @dataProvider cnpjDocuments
	 */
	public function test_save_fields_from_blocks_request_stores_cnpj( string $cnpj ): void {
		$order = $this->mockOrder( array( '_wc_billing/' . self::DOCUMENT_FIELD_ID => $cnpj ) );
		$order->expects( 'save' )->once();

		$this->controller->saveFieldsFromBlocksRequest( $order );

		self::assertSame(
			array(
				'_billing_persontype' => '2',
				'_billing_cpf'        => '',
				'_billing_cnpj'       => $cnpj,
			),
			$this->savedMeta
		);
	}

	public function cnpjDocuments(): array {
		return array(
			'numeric'      => array( self::VALID_CNPJ ),
			'alphanumeric' => array( self::VALID_ALPHA_CNPJ ),
		);
	}

	public function test_save_fields_from_blocks_request_falls_back_to_shipping_document(): void {
		$order = $this->mockOrder( array( '_wc_shipping/' . self::DOCUMENT_FIELD_ID => self::VALID_CPF ) );
		$order->expects( 'save' )->once();

		$this->controller->saveFieldsFromBlocksRequest( $order );

		self::assertSame( self::VALID_CPF, $this->savedMeta['_billing_cpf'] );
	}

	public function test_save_fields_from_blocks_request_prefers_billing_document(): void {
		$order = $this->mockOrder(
			array(
				'_wc_billing/' . self::DOCUMENT_FIELD_ID  => self::VALID_CPF,
				'_wc_shipping/' . self::DOCUMENT_FIELD_ID => self::VALID_CNPJ,
			)
		);
		$order->expects( 'save' )->once();

		$this->controller->saveFieldsFromBlocksRequest( $order );

		self::assertSame( '1', $this->savedMeta['_billing_persontype'] );
		self::assertSame( self::VALID_CPF, $this->savedMeta['_billing_cpf'] );
	}

	public function test_save_fields_from_blocks_request_skips_order_without_document(): void {
		$order = $this->mockOrder( array() );
		$order->shouldNotReceive( 'save' );

		$this->controller->saveFieldsFromBlocksRequest( $order );

		self::assertSame( array(), $this->savedMeta );
	}

	/*
	 * saveAddressFieldsFromBlocksRequest
	 */

	public function test_save_address_fields_from_blocks_request_copies_both_addresses(): void {
		$order = $this->mockOrder(
			array(
				'_wc_billing/' . self::NUMBER_FIELD_ID        => '100',
				'_wc_billing/' . self::NEIGHBORHOOD_FIELD_ID  => 'Centro',
				'_wc_shipping/' . self::NUMBER_FIELD_ID       => '200',
				'_wc_shipping/' . self::NEIGHBORHOOD_FIELD_ID => 'Bela Vista',
			)
		);
		$order->expects( 'save' )->once();

		$this->controller->saveAddressFieldsFromBlocksRequest( $order );

		self::assertSame(
			array(
				'_billing_number'        => '100',
				'_shipping_number'       => '200',
				'_billing_neighborhood'  => 'Centro',
				'_shipping_neighborhood' => 'Bela Vista',
			),
			$this->savedMeta
		);
	}

	public function test_save_address_fields_from_blocks_request_falls_back_to_billing_address(): void {
		$order = $this->mockOrder(
			array(
				'_wc_billing/' . self::NUMBER_FIELD_ID       => '100',
				'_wc_billing/' . self::NEIGHBORHOOD_FIELD_ID => 'Centro',
			)
		);
		$order->expects( 'save' )->once();

		$this->controller->saveAddressFieldsFromBlocksRequest( $order );

		self::assertSame( '100', $this->savedMeta['_shipping_number'] );
		self::assertSame( 'Centro', $this->savedMeta['_shipping_neighborhood'] );
	}

	/*
	 * Assets
	 */

	public function test_enqueue_blocks_assets_loads_script_and_style_on_checkout(): void {
		$this->defineAssetConstants();
		Functions\when( 'is_checkout' )->justReturn( true );

		Functions\expect( 'wp_enqueue_script' )
			->once()
			->with( 'me-checkout-blocks', MELHORENVIO_URL . '/assets/js/me-checkout-blocks.js', array(), MELHORENVIO_VERSION, true );
		Functions\expect( 'wp_enqueue_style' )
			->once()
			->with( 'me-checkout-blocks', MELHORENVIO_URL . '/assets/css/me-checkout-blocks.css', array(), MELHORENVIO_VERSION );

		$this->controller->enqueueBlocksAssets();
	}

	public function test_enqueue_blocks_assets_skips_non_checkout_pages(): void {
		Functions\when( 'is_checkout' )->justReturn( false );
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_enqueue_style' )->never();

		$this->controller->enqueueBlocksAssets();
	}

	public function test_enqueue_assets_adds_inline_document_script_on_checkout(): void {
		Functions\when( 'is_checkout' )->justReturn( true );

		Functions\expect( 'wp_add_inline_script' )
			->once()
			->with(
				'woocommerce',
				Mockery::on(
					static function ( $script ): bool {
						return is_string( $script )
							&& strpos( $script, '#billing_persontype' ) !== false
							&& strpos( $script, '#billing_cpf' ) !== false
							&& strpos( $script, '#billing_cnpj' ) !== false
							&& strpos( $script, '(jQuery)' ) !== false;
					}
				)
			);

		$this->controller->enqueueAssets();
	}

	public function test_enqueue_assets_skips_non_checkout_pages(): void {
		Functions\when( 'is_checkout' )->justReturn( false );
		Functions\expect( 'wp_add_inline_script' )->never();

		$this->controller->enqueueAssets();
	}

	/*
	 * Third-party checkout plugins
	 */

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @dataProvider knownCheckoutPluginConstants
	 */
	public function test_everything_is_disabled_when_external_checkout_plugin_is_active( string $constant ): void {
		define( $constant, '/plugins/' . $constant . '.php' );

		Functions\when( 'is_checkout' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( 'optional' );
		Functions\expect( 'update_option' )->never();
		Functions\expect( 'wc_add_notice' )->never();
		Functions\expect( 'update_post_meta' )->never();
		Functions\expect( 'woocommerce_register_additional_checkout_field' )->never();
		Functions\expect( 'wp_enqueue_script' )->never();
		Functions\expect( 'wp_enqueue_style' )->never();
		Functions\expect( 'wp_add_inline_script' )->never();

		$order = Mockery::mock( 'WC_Order' );
		$order->shouldNotReceive( 'get_meta', 'update_meta_data', 'save' );

		$fields = array( 'billing' => array( 'billing_phone' => array( 'required' => false ) ) );

		self::assertSame( $fields, $this->controller->addDocumentFields( $fields ) );
		self::assertSame( $fields, $this->controller->addAddressFields( $fields ) );

		$this->controller->ensureBlocksPhoneRequired();
		$this->controller->validateFields();
		$this->controller->saveFields( $order );
		$this->controller->registerBlocksCheckoutField();
		$this->controller->registerBlocksAddressFields();
		$this->controller->saveFieldsFromBlocksRequest( $order );
		$this->controller->saveAddressFieldsFromBlocksRequest( $order );
		$this->controller->enqueueBlocksAssets();
		$this->controller->enqueueAssets();
	}

	public function knownCheckoutPluginConstants(): array {
		return array(
			'Link Nacional calculator' => array( 'WC_BETTER_SHIPPING_CALCULATOR_FOR_BRAZIL_FILE' ),
			'Brazilian Market'         => array( 'CSBMW_PLUGIN_FILE' ),
		);
	}

	/*
	 * Helpers
	 */

	private function givenValidCheckoutPost( array $overrides ): void {
		$_POST = array_merge(
			array(
				'billing_persontype' => '1',
				'billing_cpf'        => self::VALID_CPF,
				'billing_cnpj'       => '',
				'billing_phone'      => '(11) 99999-8888',
				'billing_postcode'   => '01310-100',
			),
			$overrides
		);
	}

	private function collectNotices(): void {
		Functions\when( 'wc_add_notice' )->alias(
			function ( string $message, string $type ): void {
				self::assertSame( 'error', $type );
				$this->notices[] = $message;
			}
		);
	}

	/**
	 * WooCommerce saves the order right after 'woocommerce_checkout_create_order', so saveFields()
	 * must only stage meta on the order (works with and without HPOS) and never save it itself.
	 *
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function orderCollectingMeta() {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'update_meta_data' )->andReturnUsing(
			function ( string $key, $value ): void {
				$this->savedMeta[ $key ] = $value;
			}
		);
		$order->shouldNotReceive( 'save' );

		return $order;
	}

	private function collectRegisteredFields(): void {
		Functions\when( 'woocommerce_register_additional_checkout_field' )->alias(
			function ( array $field ): void {
				$this->registeredFields[] = $field;
			}
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function registerDocumentField(): array {
		$this->collectRegisteredFields();

		$this->controller->registerBlocksCheckoutField();

		self::assertCount( 1, $this->registeredFields );

		return $this->registeredFields[0];
	}

	/**
	 * @param array<string, string> $meta
	 * @return \Mockery\MockInterface&\WC_Order
	 */
	private function mockOrder( array $meta ) {
		$order = Mockery::mock( 'WC_Order' );
		$order->allows( 'get_meta' )->andReturnUsing(
			static function ( string $key ) use ( $meta ) {
				return $meta[ $key ] ?? '';
			}
		);
		$order->allows( 'update_meta_data' )->andReturnUsing(
			function ( string $key, $value ): void {
				$this->savedMeta[ $key ] = $value;
			}
		);

		return $order;
	}

	private function defineAssetConstants(): void {
		if ( ! defined( 'MELHORENVIO_URL' ) ) {
			define( 'MELHORENVIO_URL', 'https://example.test/wp-content/plugins/melhor-envio-cotacao' );
		}

		if ( ! defined( 'MELHORENVIO_VERSION' ) ) {
			define( 'MELHORENVIO_VERSION', '9.9.9' );
		}
	}
}
