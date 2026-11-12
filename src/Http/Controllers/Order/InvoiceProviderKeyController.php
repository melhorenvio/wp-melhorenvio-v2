<?php

declare(strict_types=1);

namespace MelhorEnvio\Http\Controllers\Order;

use MelhorEnvio\Http\Controllers\Contracts\ControllerInterface;
use MelhorEnvio\Services\Order\InvoiceKeyResolverService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class InvoiceProviderKeyController implements ControllerInterface {

	private InvoiceKeyResolverService $resolver;

	public function __construct( InvoiceKeyResolverService $resolver ) {
		$this->resolver = $resolver;
	}

	public function register(): void {
		add_action( 'woocommerce_before_order_object_save', array( $this, 'applyInvoiceKey' ), 10, 1 );
	}

	public function applyInvoiceKey( $order ): void {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		$invoiceKey = $this->resolver->resolve( $order );

		if ( $invoiceKey === null ) {
			return;
		}

		$currentKey = $order->get_meta( OrderInvoiceKeyMetaBoxController::META_KEY, true );
		$currentKey = is_string( $currentKey ) ? $currentKey : '';

		if ( $currentKey === $invoiceKey ) {
			return;
		}

		if ( $currentKey !== '' && ! in_array( $currentKey, $this->resolver->providerKeys( $order ), true ) ) {
			return;
		}

		$order->update_meta_data( OrderInvoiceKeyMetaBoxController::META_KEY, $invoiceKey );
	}
}
