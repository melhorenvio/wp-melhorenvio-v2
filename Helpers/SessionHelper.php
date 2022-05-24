<?php

namespace MelhorEnvio\Helpers;

/**
 * Session helper class
 */
class SessionHelper {

	/**
	 * Helper to start the session if it has not been started.
	 *
	 * @return void
	 */
	public static function initIfNotExists() {
		if ( ! self::exists() ) {
			@session_start();
		}
	}

	/**
	 * Helper to check if there is a session started.
	 *
	 * @return bool
	 */
	public static function exists() {
		return ! empty( session_id() );
	}
}
