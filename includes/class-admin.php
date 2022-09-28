<?php

namespace App;

use MelhorEnvio\Helpers\EscapeAllowedTags;

/**
 * Admin Pages Handler
 */
class Admin
{


	public function __construct()
	{
		add_action('admin_menu', array($this, 'admin_menu'));
	}

	/**
	 * Register our menu page
	 *
	 * @return void
	 */
	public function admin_menu()
	{
		global $submenu;

		$capability = 'manage_woocommerce';
		$slug       = 'melhor-envio';

		$hook = add_menu_page(
			__('Melhor Envio', 'textdomain'),
			__('Melhor Envio', 'textdomain'),
			$capability,
			$slug,
			array($this, 'plugin_page'),
			'dashicons-thumbs-up'
		);

		if (current_user_can($capability)) {
			$submenu[$slug][] = array(__('Meus pedidos', 'textdomain'), $capability, 'admin.php?page=' . $slug . '#/pedidos');
			$submenu[$slug][] = array(__('Configurações', 'textdomain'), $capability, 'admin.php?page=' . $slug . '#/configuracoes');
			$submenu[$slug][] = array(__('Token', 'textdomain'), $capability, 'admin.php?page=' . $slug . '#/token');
		}

		add_action('load-' . $hook, array($this, 'init_hooks'));
	}

	/**
	 * Initialize our hooks for the admin page
	 *
	 * @return void
	 */
	public function init_hooks()
	{
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	/**
	 * Load scripts and styles for the app
	 *
	 * @return void
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_style('melhorenvio-style');
		wp_enqueue_style('melhorenvio-admin');
		wp_enqueue_script('melhorenvio-admin');
	}

	/**
	 * Render our admin page
	 *
	 * @return void
	 */
	public function plugin_page()
	{
		echo wp_kses(
			'<div class="wrap"><div id="vue-admin-app"></div></div>',
			EscapeAllowedTags::allow_tags(["div"])
		);
	}
}
