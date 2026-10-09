<?php
// phpcs:ignoreFile
/**
 * Minimal in-memory stand-ins for WordPress classes that src/ instantiates with `new`
 * (and therefore can't be replaced by Mockery). Only the API surface used by src/ is implemented.
 * Classes that are only type-hinted (WP_Comment, WP_Post...) should be mocked with Mockery instead.
 */

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {

		/** @var array<string|int, string[]> */
		public $errors = array();

		/** @var array<string|int, mixed> */
		public $error_data = array();

		/**
		 * @param string|int $code
		 * @param mixed      $data
		 */
		public function __construct( $code = '', string $message = '', $data = '' ) {
			if ( $code === '' ) {
				return;
			}

			$this->errors[ $code ][] = $message;

			if ( $data !== '' ) {
				$this->error_data[ $code ] = $data;
			}
		}

		/** @return string|int */
		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes[0] ?? '';
		}

		/** @param string|int $code */
		public function get_error_message( $code = '' ): string {
			$code = $code === '' ? $this->get_error_code() : $code;
			return $this->errors[ $code ][0] ?? '';
		}

		/**
		 * @param string|int $code
		 * @return mixed
		 */
		public function get_error_data( $code = '' ) {
			$code = $code === '' ? $this->get_error_code() : $code;
			return $this->error_data[ $code ] ?? null;
		}

		public function has_errors(): bool {
			return ! empty( $this->errors );
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {

		/** @var mixed */
		public $data;

		/** @var int */
		public $status;

		/** @var array<string, string> */
		public $headers;

		/** @param mixed $data */
		public function __construct( $data = null, int $status = 200, array $headers = array() ) {
			$this->data    = $data;
			$this->status  = $status;
			$this->headers = $headers;
		}

		/** @return mixed */
		public function get_data() {
			return $this->data;
		}

		/** @param mixed $data */
		public function set_data( $data ): void {
			$this->data = $data;
		}

		public function get_status(): int {
			return $this->status;
		}

		public function set_status( int $status ): void {
			$this->status = $status;
		}

		public function get_headers(): array {
			return $this->headers;
		}

		public function header( string $key, string $value ): void {
			$this->headers[ $key ] = $value;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Build it in tests: `new WP_REST_Request( 'POST', '/route' )` + set_param/set_header/set_body.
	 */
	class WP_REST_Request {

		private string $method;

		private string $route;

		private array $params = array();

		private array $headers = array();

		private array $files = array();

		private string $body = '';

		public function __construct( string $method = '', string $route = '' ) {
			$this->method = $method;
			$this->route  = $route;
		}

		public function get_method(): string {
			return $this->method;
		}

		public function get_route(): string {
			return $this->route;
		}

		/** @return mixed */
		public function get_param( string $key ) {
			return $this->params[ $key ] ?? null;
		}

		/** @param mixed $value */
		public function set_param( string $key, $value ): void {
			$this->params[ $key ] = $value;
		}

		public function get_params(): array {
			return $this->params;
		}

		public function has_param( string $key ): bool {
			return array_key_exists( $key, $this->params );
		}

		public function get_json_params(): ?array {
			$decoded = json_decode( $this->body, true );
			return is_array( $decoded ) ? $decoded : null;
		}

		public function get_body_params(): array {
			return $this->params;
		}

		public function get_body(): string {
			return $this->body;
		}

		public function set_body( string $body ): void {
			$this->body = $body;
		}

		public function get_header( string $key ): ?string {
			return $this->headers[ self::canonical( $key ) ] ?? null;
		}

		public function set_header( string $key, string $value ): void {
			$this->headers[ self::canonical( $key ) ] = $value;
		}

		public function get_headers(): array {
			return $this->headers;
		}

		public function get_file_params(): array {
			return $this->files;
		}

		public function set_file_params( array $files ): void {
			$this->files = $files;
		}

		private static function canonical( string $key ): string {
			return str_replace( '-', '_', strtolower( $key ) );
		}
	}
}
