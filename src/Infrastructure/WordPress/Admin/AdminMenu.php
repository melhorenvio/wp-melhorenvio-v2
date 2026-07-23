<?php

declare(strict_types=1);

namespace MelhorEnvio\Infrastructure\WordPress\Admin;

use MelhorEnvio\Core\Container;

final class AdminMenu {

	private string $pageTitle;
	private string $menuTitle;
	private string $capability;
	private string $menuSlug;
	private string $parentSlug;
	private Container $container;

	public function __construct(
		Container $container,
		string $pageTitle  = 'Melhor Envio',
		string $menuTitle  = 'Melhor Envio',
		string $capability = 'manage_options',
		string $menuSlug   = 'melhor-integrador',
		string $parentSlug = 'woocommerce'
	) {
		$this->container  = $container;
		$this->pageTitle  = $pageTitle;
		$this->menuTitle  = $menuTitle;
		$this->capability = $capability;
		$this->menuSlug   = $menuSlug;
		$this->parentSlug = $parentSlug;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addSubmenuPage' ), 20 );
		add_action( 'admin_head', array( $this, 'addAdminStyles' ) );
		add_filter( 'admin_body_class', array( $this, 'addBodyClass' ) );
	}

	public function addSubmenuPage(): void {
		add_submenu_page(
			$this->parentSlug,
			$this->pageTitle,
			$this->menuTitle,
			$this->capability,
			$this->menuSlug,
			array( $this, 'renderPage' )
		);
	}

	public function renderPage(): void {
		$secretManager    = $this->container->get( SecretManager::class );
		$signatureManager = $this->container->get( SignatureManager::class );

		$adminPage = new AdminPage( $this->menuSlug, $secretManager, $signatureManager );
		$adminPage->render();
	}

	public function getMenuSlug(): string {
		return $this->menuSlug;
	}

	public function addAdminStyles(): void {
		$screen = get_current_screen();
		if ( ! $screen || $screen->id !== $this->getScreenId() ) {
			return;
		}
		$screenId = esc_attr( $this->getScreenId() );
		?>
		<style>
		.melhor-envio-integrador-page #wpcontent, .melhor-envio-integrador-page #wpbody-content {
			overflow-x: initial !important;
		}

		.melhor-envio-integrador-page #wpcontent, .melhor-envio-integrador-page.<?php echo $screenId; ?> #wpbody-content {
			padding: 0;
			min-height: calc(100vh - 32px);
		}

		.melhor-envio-integrador-page #wpbody-content > .notice,
		.melhor-envio-integrador-page #wpbody-content > .me-alert {
			margin-left: 20px;
			margin-right: 20px;
			margin-top: 8px;
		}
		</style>
		<?php
	}

	public function addBodyClass( string $classes ): string {
		$screen = get_current_screen();
		if ( $screen && $screen->id === $this->getScreenId() ) {
			$classes .= ' melhor-envio-integrador-page';
		}
		return $classes;
	}

	private function getScreenId(): string {
		return $this->parentSlug . '_page_' . $this->menuSlug;
	}
}
