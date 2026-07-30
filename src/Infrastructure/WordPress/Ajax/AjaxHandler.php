<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Ajax;

abstract class AjaxHandler {

	protected string $action;

	protected string $capability;

	protected bool $public = false;

	public function __construct( string $action, string $capability = 'manage_options', bool $public = false ) {
		$this->action     = $action;
		$this->capability = $capability;
		$this->public     = $public;
	}

	public function register(): void {
		$hook = $this->public ? 'wp_ajax_nopriv_' : 'wp_ajax_';
		add_action( $hook . $this->action, array( $this, 'handle' ) );
	}

	public function handle(): void {
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) );
		if ( ! wp_verify_nonce( $nonce, $this->action ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce.' ), 403 );
			return;
		}

		if ( ! $this->public && ! current_user_can( $this->capability ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
			return;
		}

		$this->process();
	}

	abstract protected function process(): void;

	/**
	 * @param mixed $default
	 * @return mixed
	 */
	protected function getInput( string $key, $default = null ) {
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default;
	}

	/**
	 * @param mixed $data
	 */
	protected function sendSuccess( $data, int $status_code = 200 ): void {
		wp_send_json_success( $data, $status_code );
	}

	/**
	 * @param mixed $data
	 */
	protected function sendError( $data, int $status_code = 400 ): void {
		wp_send_json_error( $data, $status_code );
	}
}
