<?php
// phpcs:ignoreFile
/**
 * In-memory fakes for WooCommerce classes that src/ instantiates with `new`, calls statically,
 * or extends. State lives in static properties and is reset by WooCommerceFakes::reset()
 * (called from MelhorEnvio\Tests\TestCase::tearDown()).
 *
 * Classes that are only type-hinted or received as arguments (WC_Order, WC_Product,
 * WC_Order_Item_Product...) should be mocked with Mockery instead.
 */

if ( ! class_exists( 'WC_Webhook' ) ) {
	/**
	 * Seed: WC_Webhook::$records[ $id ] = array( 'delivery_url' => '...', 'topic' => 'order.updated' );
	 * Any get_<prop>() reads from the seeded record. delete() pushes the id into WC_Webhook::$deleted.
	 */
	class WC_Webhook {

		/** @var array<int, array<string, mixed>> */
		public static $records = array();

		/** @var int[] */
		public static $deleted = array();

		/** @var int */
		private $id;

		public function __construct( $id = 0 ) {
			$this->id = (int) $id;
		}

		public function get_id(): int {
			return $this->id;
		}

		public function delete( bool $force = false ): bool {
			self::$deleted[] = $this->id;
			return true;
		}

		/** @return mixed */
		public function __call( string $name, array $args ) {
			if ( strpos( $name, 'get_' ) === 0 ) {
				return self::$records[ $this->id ][ substr( $name, 4 ) ] ?? '';
			}

			throw new BadMethodCallException( "WC_Webhook fake does not implement {$name}()." );
		}
	}
}

if ( ! class_exists( 'WC_Data_Store' ) ) {
	/**
	 * Seed: WC_Data_Store::$stores['webhook'] = Mockery::mock(); (or any object).
	 */
	class WC_Data_Store {

		/** @var array<string, object> */
		public static $stores = array();

		public static function load( string $object_type ): object {
			if ( ! isset( self::$stores[ $object_type ] ) ) {
				throw new RuntimeException( "No fake data store seeded for '{$object_type}'." );
			}

			return self::$stores[ $object_type ];
		}
	}
}

if ( ! class_exists( 'WC_Shipping_Zone' ) ) {
	/**
	 * Seed an existing zone: WC_Shipping_Zone::$records[ $id ] = array(
	 *     'locations' => array( (object) array( 'type' => 'country', 'code' => 'BR' ) ),
	 *     'methods'   => array( (object) array( 'id' => 'melhor_envio', 'instance_id' => 7 ) ),
	 * );
	 * Every instance (seeded or new) is pushed into WC_Shipping_Zone::$instances for assertions.
	 */
	class WC_Shipping_Zone {

		/** @var array<int, array<string, mixed>> */
		public static $records = array();

		/** @var WC_Shipping_Zone[] */
		public static $instances = array();

		public $id;

		public $zone_name = '';

		public $locations = array();

		public $methods = array();

		public $added_methods = array();

		public $deleted_method_instance_ids = array();

		public $saved = false;

		public function __construct( $zone = null ) {
			$this->id = $zone === null ? null : (int) $zone;

			if ( $this->id !== null && isset( self::$records[ $this->id ] ) ) {
				$this->locations = self::$records[ $this->id ]['locations'] ?? array();
				$this->methods   = self::$records[ $this->id ]['methods'] ?? array();
				$this->zone_name = self::$records[ $this->id ]['zone_name'] ?? '';
			}

			self::$instances[] = $this;
		}

		public function get_id() {
			return $this->id;
		}

		public function get_zone_name(): string {
			return $this->zone_name;
		}

		public function set_zone_name( string $name ): void {
			$this->zone_name = $name;
		}

		public function get_zone_locations(): array {
			return $this->locations;
		}

		public function add_location( string $code, string $type ): void {
			$this->locations[] = (object) array( 'code' => $code, 'type' => $type );
		}

		public function get_shipping_methods( bool $enabled_only = false, string $context = 'admin' ): array {
			return $this->methods;
		}

		public function add_shipping_method( string $type ): int {
			$this->added_methods[] = $type;
			return count( $this->added_methods );
		}

		public function delete_shipping_method( $instance_id ): bool {
			$this->deleted_method_instance_ids[] = (int) $instance_id;
			return true;
		}

		public function save(): int {
			$this->saved = true;
			return (int) $this->id;
		}
	}
}

if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
	/**
	 * Seed: WC_Shipping_Zones::$zones = array( array( 'id' => 1 ) );
	 *       WC_Shipping_Zones::$matching_zone = new WC_Shipping_Zone( 1 );
	 */
	class WC_Shipping_Zones {

		/** @var array<int, array<string, mixed>> */
		public static $zones = array();

		/** @var WC_Shipping_Zone|null */
		public static $matching_zone;

		public static function get_zones( string $context = 'admin' ): array {
			return self::$zones;
		}

		public static function get_zone_matching_package( $package ): WC_Shipping_Zone {
			return self::$matching_zone ?? new WC_Shipping_Zone( 0 );
		}
	}
}

if ( ! class_exists( 'WC_Shipping_Method' ) ) {
	/**
	 * Base class for MelhorEnvioShippingService. get_option() reads $instance_settings / $settings
	 * (seed via WC_Shipping_Method::$seed_settings before instantiation); add_rate() records into $rates.
	 */
	abstract class WC_Shipping_Method {

		/** @var array<string, mixed> Settings applied to the next instance created. */
		public static $seed_settings = array();

		public $id = '';

		public $instance_id = 0;

		public $method_title = '';

		public $method_description = '';

		public $title = '';

		public $enabled = 'yes';

		public $supports = array( 'products' );

		public $form_fields = array();

		public $instance_form_fields = array();

		public $settings = array();

		public $instance_settings = array();

		public $rates = array();

		public function __construct( $instance_id = 0 ) {
			$this->instance_id       = (int) $instance_id;
			$this->instance_settings = self::$seed_settings;
		}

		public function init_settings(): void {
			$this->settings = self::$seed_settings;
		}

		/** @return mixed */
		public function get_option( string $key, $empty_value = null ) {
			if ( array_key_exists( $key, $this->instance_settings ) ) {
				return $this->instance_settings[ $key ];
			}

			return $this->settings[ $key ] ?? $empty_value;
		}

		public function add_rate( array $args = array() ): void {
			$this->rates[] = $args;
		}

		public function process_admin_options(): bool {
			return true;
		}
	}
}

final class WooCommerceFakes {

	public static function reset(): void {
		WC_Webhook::$records             = array();
		WC_Webhook::$deleted             = array();
		WC_Data_Store::$stores           = array();
		WC_Shipping_Zone::$records       = array();
		WC_Shipping_Zone::$instances     = array();
		WC_Shipping_Zones::$zones        = array();
		WC_Shipping_Zones::$matching_zone = null;
		WC_Shipping_Method::$seed_settings = array();
	}
}
