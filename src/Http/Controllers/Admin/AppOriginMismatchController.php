<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Admin;

use MelhorEnvio\Http\Controllers\AjaxHandlerContract;

defined( 'ABSPATH' ) || exit;

// Registra quando a assinatura foi entregue a uma origem fora de AppOrigins (versão de observação).
final class AppOriginMismatchController extends AjaxHandlerContract {

	public const ACTION = 'melhor_envio_app_origin_mismatch';

	public function __construct() {
		parent::__construct( self::ACTION );
	}

	protected function process(): void {
		$origin = substr( (string) $this->getInput( 'origin', '' ), 0, 200 );

		wc_get_logger()->warning(
			'Assinatura enviada por postMessage para origem fora da lista do Melhor Integrador.',
			array(
				'source' => 'melhor-envio-cotacao',
				'origin' => $origin,
			)
		);

		$this->sendSuccess( null );
	}
}
