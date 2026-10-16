<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Concerns;

use MelhorEnvio\Services\Auth\SecretService;
use MelhorEnvio\Services\Auth\SignatureService;
use WP_REST_Request;

trait ValidatesAuthentication {

	private ?SignatureService $signatureManager = null;

	private ?SecretService $secretManager = null;

	public function checkSignaturePermission( WP_REST_Request $request ): bool {
		$signature = $request->get_header( 'X-ME-Signature' );

		if ( empty( $signature ) || $this->signatureManager === null ) {
			return false;
		}

		return $this->signatureManager->validateSignature( $signature );
	}

	public function checkSecretPermission( WP_REST_Request $request ): bool {
		$secret = $request->get_header( 'X-ME-Secret' );

		if ( empty( $secret ) || $this->secretManager === null ) {
			return false;
		}

		$storedSecret = $this->secretManager->getSecret();

		return $storedSecret !== null && hash_equals( $storedSecret, $secret );
	}
}
