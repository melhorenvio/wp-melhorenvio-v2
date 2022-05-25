<?php

namespace MelhorEnvio\Services;

use MelhorEnvio\Helpers\SessionHelper;
use MelhorEnvio\Helpers\EscapeAllowedTags;
use MelhorEnvio\Models\Session;

/**
 * Service responsible for managing the data stored in the session
 */
class SessionNoticeService {



	const ID_NOTICES_OPTIONS = 'wp_option_notices_melhor_envio';

	const TYPE_NOTICE_DEFAULT = 'notice-error';

	const NOTICE_INFO = 'notice-info';

	const TYPES_NOTICE = array(
		'notice-error',
		'notice-warning',
		'notice-success',
		'notice-info',
	);

	const NOTICE_INVALID_TOKEN = 'Verificar seu token Melhor Envio, por favor gerar um novo token';

	/**
	 * notice-error – error message displayed with a red border
	 * notice-warning – warning message displayed with a yellow border
	 * notice-success – success message displayed with a green border
	 * notice-info - – info message displayed with a blue border
	 *
	 * @param text   $message
	 * @param string $type
	 * @return bool
	 */
	public function add( $text, $type ) {
		$type = ( in_array( $type, self::TYPES_NOTICE ) )
			? $type
			: self::TYPE_NOTICE_DEFAULT;

		$notices = $this->get();

		$hash = hash( 'sha512', $text );

		$notices[ $hash ] = $this->formatHtml( $text, $type );

		if ( ! empty( $notices ) ) {
			return update_option( self::ID_NOTICES_OPTIONS, $notices );
		}

		return add_option( self::ID_NOTICES_OPTIONS, $notices );
	}

	/**
	 * @param string $text
	 * @param string $type
	 */
	private function formatHtml( $text, $type ) {
		return sprintf(
			'<div class="notice %s is-dismissible"> 
                <p><strong>Atenção usuário do Melhor Envio</strong></p>
                <p>%s</p>
                <p><a href="%s">Fechar</a></p>
            </div>',
			$type,
			$text,
			get_admin_url() . 'admin-ajax.php?action=remove_notices&id=' . hash( 'sha512', $text )
		);
	}

	/**
	 * Function to check whether to display and insert the search form alert on the administrative page
	 */
	public function showNotices() {
		 $notices = $this->get();
		foreach ( $notices as $hash => $notice ) {
			add_action(
				'admin_notices',
				function () use ( $notice ) {
					echo wp_kses( $notice, EscapeAllowedTags::allow_tags( array( 'div', 'p', 'a' ) ) );
				}
			);
		}
	}


	/**
	 * function to remove notice in session by key.
	 *
	 * @param string $hash
	 * @return void
	 */
	public function remove( $hash ) {
		$notices = $this->get();
		unset( $notices[ $hash ] );
		update_option( self::ID_NOTICES_OPTIONS, $notices );
		wp_redirect( $_SERVER['HTTP_REFERER'] );
		exit;
	}

	/**
	 * @return bool
	 */
	public function clear() {
		return update_option( self::ID_NOTICES_OPTIONS, array() );
	}

	public function removeNoticeTokenInvalid() {
		$notices = $this->get();
		unset( $notices[ hash( 'sha512', self::NOTICE_INVALID_TOKEN ) ] );
		return update_option( self::ID_NOTICES_OPTIONS, $notices );
	}

	/**
	 * function to list all notices
	 *
	 * @return bool|array
	 */
	public function get() {
		 return get_option( self::ID_NOTICES_OPTIONS, array() );
	}
}
