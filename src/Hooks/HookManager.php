<?php

declare(strict_types=1);

namespace MelhorEnvio\Hooks;

use MelhorEnvio\Core\Container;
use MelhorEnvio\Http\Controllers\Admin\AdminMenuController;
use MelhorEnvio\Http\Controllers\Admin\ModeNoticeController;
use MelhorEnvio\Http\Controllers\Order\OrderBulkImportController;
use MelhorEnvio\Http\Controllers\Order\OrderBulkImportNoticeController;
use MelhorEnvio\Http\Controllers\Order\OrderInvoiceKeyMetaBoxController;
use MelhorEnvio\Http\Controllers\Order\OrderNoteInvoiceKeyController;
use MelhorEnvio\Services\Auth\SignatureService;
use MelhorEnvio\Http\Controllers\Order\NFeXmlUploadController;
use MelhorEnvio\Http\Controllers\Quotation\QuotationController;
use MelhorEnvio\Http\Controllers\Checkout\CheckoutFieldsController;
use MelhorEnvio\Http\Controllers\Frontend\ProductShippingCalculatorController;
use MelhorEnvio\Http\Controllers\Auth\DisconnectController;
use MelhorEnvio\Http\Controllers\Order\OrderMetaBackfillController;
use MelhorEnvio\Http\Controllers\Auth\QuotationTokenController;
use MelhorEnvio\Http\Controllers\Auth\SaveSecretController;
use MelhorEnvio\Http\Controllers\Settings\GetSettingsController;
use MelhorEnvio\Http\Controllers\Settings\SaveSettingsController;
use MelhorEnvio\Services\Order\ForceImportService;
use MelhorEnvio\Services\Shipping\MelhorEnvioShippingService;
use MelhorEnvio\Services\Webhook\MelhorIntegradorWebhookService;

final class HookManager {

	private Container $container;

	public function __construct( Container $container ) {
		$this->container = $container;
	}

	public function register(): void {
		$adminMenu = $this->container->get( AdminMenuController::class );
		$adminMenu->register();

		$modeNotice = $this->container->get( ModeNoticeController::class );
		$modeNotice->register();

		$saveSecretEndpoint = $this->container->get( SaveSecretController::class );
		$saveSecretEndpoint->register();

		$quotationTokenEndpoint = $this->container->get( QuotationTokenController::class );
		$quotationTokenEndpoint->register();

		$disconnectEndpoint = $this->container->get( DisconnectController::class );
		$disconnectEndpoint->register();

		$getSettingsEndpoint = $this->container->get( GetSettingsController::class );
		$getSettingsEndpoint->register();

		$saveSettingsEndpoint = $this->container->get( SaveSettingsController::class );
		$saveSettingsEndpoint->register();

		$quotationAjaxHandler = $this->container->get( QuotationController::class );
		$quotationAjaxHandler->register();

		$nfeXmlUploadHandler = $this->container->get( NFeXmlUploadController::class );
		$nfeXmlUploadHandler->register();

		$productShippingCalculator = $this->container->get( ProductShippingCalculatorController::class );
		$productShippingCalculator->register();

		$checkoutFieldsManager = $this->container->get( CheckoutFieldsController::class );
		$checkoutFieldsManager->register();

		$orderMetaBackfill = $this->container->get( OrderMetaBackfillController::class );
		$orderMetaBackfill->register();

		$orderInvoiceKeyMetaBox = $this->container->get( OrderInvoiceKeyMetaBoxController::class );
		$orderInvoiceKeyMetaBox->register();

		$orderNoteInvoiceKey = $this->container->get( OrderNoteInvoiceKeyController::class );
		$orderNoteInvoiceKey->register();

		// Sincronização sob demanda oculta até o backend da Tray Native suportá-la.
		// $orderBulkImport = $this->container->get( OrderBulkImportController::class );
		// $orderBulkImport->register();

		$orderBulkImportNotice = $this->container->get( OrderBulkImportNoticeController::class );
		$orderBulkImportNotice->register();

		$this->registerShippingMethod();
		$this->registerLogoutHook();
		$this->registerSslVerifyFilters();
		$this->registerWcAuthApproveGuard();
		$this->registerHiddenOrderItemMeta();
		$this->registerWebhookDeliveryFilter();
		$this->registerWebhookDeliveryHashUpdater();
	}

	private function registerHiddenOrderItemMeta(): void {
		add_filter(
			'woocommerce_hidden_order_itemmeta',
			static function ( array $hidden ): array {
				return array_merge(
					$hidden,
					array(
						'me_service_id',
						'me_service_name',
						'me_company_id',
						'me_company_name',
						'me_delivery_time',
					)
				);
			}
		);
	}

	private function registerShippingMethod(): void {
		add_filter(
			'woocommerce_shipping_methods',
			static function ( array $methods ): array {
				$methods['melhor_envio'] = MelhorEnvioShippingService::class;
				return $methods;
			}
		);
	}

	private function registerLogoutHook(): void {
		add_action( 'wp_logout', array( $this, 'onUserLogout' ) );
	}

	private function registerSslVerifyFilters(): void {
		add_filter( 'https_ssl_verify', '__return_false' );
		add_filter( 'https_local_ssl_verify', '__return_false' );
	}

	private function registerWcAuthApproveGuard(): void {
		add_action(
			'woocommerce_auth_page_footer',
			static function (): void {
				?>
				<script>
				(function () {
					var btn = document.querySelector('.wc-auth-approve');
					if (!btn) return;
					btn.addEventListener('click', function (e) {
						if (btn.dataset.clicked) {
							e.preventDefault();
							return;
						}
						btn.dataset.clicked = '1';
						btn.style.opacity = '0.6';
						btn.style.pointerEvents = 'none';
						btn.textContent = btn.textContent + '...';
					});
				})();
				</script>
				<?php
			}
		);
	}

	public function onUserLogout(): void {
		$signatureManager = $this->container->get( SignatureService::class );
		$signatureManager->deleteSignature();
	}

	private function registerWebhookDeliveryFilter(): void {
		$webhooks    = $this->container->get( MelhorIntegradorWebhookService::class );
		$forceImport = $this->container->get( ForceImportService::class );

		add_filter(
			'woocommerce_webhook_should_deliver',
			static function ( bool $should_deliver, \WC_Webhook $webhook, int $arg ) use ( $webhooks, $forceImport ): bool {
				if ( ! $should_deliver || ! $webhooks->isMelhorIntegradorWebhook( $webhook ) || 'order.updated' !== $webhook->get_topic() ) {
					return $should_deliver;
				}

				$order = wc_get_order( $arg );
				if ( ! $order instanceof \WC_Order ) {
					return false;
				}

				// Importação forçada pelo lojista: entrega mesmo sem mudança relevante no pedido.
				return $forceImport->isActive( $order )
					|| self::buildOrderHash( $order ) !== $order->get_meta( '_me_webhook_last_hash' );
			},
			10,
			3
		);
	}

	private function registerWebhookDeliveryHashUpdater(): void {
		$webhooks    = $this->container->get( MelhorIntegradorWebhookService::class );
		$forceImport = $this->container->get( ForceImportService::class );

		add_action(
			'woocommerce_webhook_delivery',
			static function ( array $http_args, $response, float $duration, int $arg, int $webhook_id ) use ( $webhooks, $forceImport ): void {
				$topic = self::deliveredOrderTopic( $webhooks, $response, $webhook_id );
				$order = null !== $topic ? wc_get_order( $arg ) : null;

				if ( ! $order instanceof \WC_Order ) {
					return;
				}

				// Marca de uso único: o MI já recebeu o pedido com ela.
				$forceImport->clear( $order );

				if ( 'order.updated' === $topic ) {
					$order->update_meta_data( '_me_webhook_last_hash', self::buildOrderHash( $order ) );
					$order->save_meta_data();
				}
			},
			10,
			5
		);
	}

	/**
	 * Tópico de pedido (order.created/order.updated) de uma entrega 2xx do webhook do MI,
	 * ou null quando a entrega falhou ou não é do MI.
	 *
	 * @param array|\WP_Error $response
	 */
	private static function deliveredOrderTopic( MelhorIntegradorWebhookService $webhooks, $response, int $webhookId ): ?string {
		$responseCode = (int) wp_remote_retrieve_response_code( $response );

		if ( $responseCode < 200 || $responseCode >= 300 ) {
			return null;
		}

		$webhook = new \WC_Webhook( $webhookId );
		$topic   = $webhook->get_topic();

		return in_array( $topic, array( 'order.created', 'order.updated' ), true ) && $webhooks->isMelhorIntegradorWebhook( $webhook )
			? $topic
			: null;
	}

	private static function buildOrderHash( \WC_Order $order ): string {
		$shipping_lines = array_map(
			static fn( \WC_Order_Item_Shipping $line ) => array(
				'method_id'    => $line->get_method_id(),
				'method_title' => $line->get_method_title(),
				'total'        => $line->get_total(),
			),
			$order->get_shipping_methods()
		);

		$line_items = array_map(
			static fn( \WC_Order_Item_Product $item ) => array(
				'product_id' => $item->get_product_id(),
				'quantity'   => $item->get_quantity(),
			),
			$order->get_items()
		);

		return md5(
			serialize(
				array(
					'status'         => $order->get_status(),
					'billing'        => $order->get_address( 'billing' ),
					'shipping'       => $order->get_address( 'shipping' ),
					'shipping_lines' => $shipping_lines,
					'line_items'     => $line_items,
					'invoice_key'    => $order->get_meta( '_me_invoice_key', true ),
				)
			)
		);
	}
}
