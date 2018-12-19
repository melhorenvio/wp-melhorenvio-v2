<?php
/*
Plugin Name: Melhor Envio v2
Plugin URI: https://melhorenvio.com.br
Description: Plugin para cotação e compra de fretes utilizando a API da Melhor Envio. Versão BETA
Version: 2.2.8
Author: Melhor Envio
Author URI: melhorenvio.com.br
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: baseplugin
Domain Path: /languages
*/

/**
 * Copyright (c) YEAR Your Name (email: Email). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Usages
 */
require __DIR__ . '/vendor/autoload.php';
include_once ABSPATH . '/wp-content/plugins/woocommerce/includes/class-woocommerce.php';
include_once ABSPATH . '/wp-content/plugins/woocommerce/woocommerce.php';
include_once ABSPATH . '/wp-content/plugins/woocommerce/includes/abstracts/abstract-wc-shipping-method.php';

use Controllers\OrdersController;
use Controllers\ConfigurationController;
use Controllers\TokenController;
use Controllers\PackageController;
use Controllers\UsersController;
use Controllers\CotationController;
use Controllers\WoocommerceCorreiosCalculoDeFreteNaPaginaDoProduto;
use Controllers\LogsController;
use Controllers\OptionsController;
use Models\CalculatorShow;

/**
 * Base_Plugin class
 *
 * @class Base_Plugin The class that holds the entire Base_Plugin plugin
 */
final class Base_Plugin {

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '2.2.8';

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = array();

    /**
     * Constructor for the Base_Plugin class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    public function __construct() {


        $pluginsActiveds = apply_filters( 'active_plugins', get_option( 'active_plugins' ));

        $errors = [];
        if (!in_array('woocommerce/woocommerce.php', $pluginsActiveds)) {
            $errors[] = 'Você precisa do plugin WooCommerce ativado no wordpress para utilizar o plugin do Melhor Envio';
        }

        if (!in_array('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php', $pluginsActiveds)) {
            $errors[] = 'Você precisa do plugin <a target="_blank" href="https://br.wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">WooCommerce checkout fields for Brazil</a> ativado no wordpress para utilizar o plugin do Melhor Envio';
        }

        if (!empty($errors)) {
            foreach ($errors as $err) {
                add_action( 'admin_notices', function() use ($err) {
                    echo sprintf('<div class="error">
                        <p>%s</p>
                    </div>', $err);
                });
            }
            return false;
        }
        

        $this->define_constants();

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );

        // Create the methods shippings
        foreach ( glob( plugin_dir_path( __FILE__ ) . '/services/*.php' ) as $filename ) {
            include_once $filename;
        }

    }

    /**
     * Initializes the Base_Plugin() class
     *
     * Checks for an existing Base_Plugin() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {

        static $instance = false;

        if ( ! $instance ) {
            $instance = new Base_Plugin();
        }

        return $instance;
    }

    /**
     * Magic getter to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }

        return $this->{$prop};
    }

    /**
     * Magic isset to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __isset( $prop ) {
        return isset( $this->{$prop} ) || isset( $this->container[ $prop ] );
    }

    /**
     * Define the constants
     *
     * @return void
     */
    public function define_constants() {
        define( 'BASEPLUGIN_VERSION', $this->version );
        define( 'BASEPLUGIN_FILE', __FILE__ );
        define( 'BASEPLUGIN_PATH', dirname( BASEPLUGIN_FILE ) );
        define( 'BASEPLUGIN_INCLUDES', BASEPLUGIN_PATH . '/includes' );
        define( 'BASEPLUGIN_URL', plugins_url( '', BASEPLUGIN_FILE ) );
        define( 'BASEPLUGIN_ASSETS', BASEPLUGIN_URL . '/assets' );
    }

    /**
     * Load the plugin after all plugis are loaded
     *
     * @return void
     */
    public function init_plugin() {

        
        $this->includes();
        $this->init_hooks();
        
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {

        $installed = get_option( 'baseplugin_installed' );

        if ( ! $installed ) {
            update_option( 'baseplugin_installed', time() );
        }

        update_option( 'baseplugin_version', BASEPLUGIN_VERSION );
    }

    public function deactivate() {

    }

    /**
     * Include the required files
     *
     * @return void
     */
    public function includes() {

        require_once BASEPLUGIN_INCLUDES . '/class-assets.php';

        if ( $this->is_request( 'admin' ) ) {
            require_once BASEPLUGIN_INCLUDES . '/class-admin.php';
        }

        if ( $this->is_request( 'frontend' ) ) {
            require_once BASEPLUGIN_INCLUDES . '/class-frontend.php';
        }

        if ( $this->is_request( 'ajax' ) ) {
            // require_once BASEPLUGIN_INCLUDES . '/class-ajax.php';
        }

        if ( $this->is_request( 'rest' ) ) {
            require_once BASEPLUGIN_INCLUDES . '/class-rest-api.php';
        }
    }

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function init_hooks() {
        
        $token   = new tokenController();
        $order   = new OrdersController(); 
        $users   = new UsersController();
        $conf    = new ConfigurationController();
        $cotacao = new CotationController();
        $logs    = new LogsController();
        $options = new OptionsController();

        $hideCalculator = (new CalculatorShow)->get();

        if ($hideCalculator) {
            $cotacaoProd = new WoocommerceCorreiosCalculoDeFreteNaPaginaDoProduto();
            $cotacaoProd->run();
        }

        add_action( 'init', array( $this, 'init_classes' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );

        add_action('wp_ajax_get_orders', function() {
            $order = new OrdersController();
            echo $order->getOrders();
            die;
        });

        add_action('wp_ajax_get_token', [$token, 'getToken']);
        add_action('wp_ajax_save_token', [$token, 'saveToken']);
        add_action('wp_ajax_add_order', [$order, 'sendOrder']);
        add_action('wp_ajax_remove_order', [$order, 'removeOrder']);
        add_action('wp_ajax_cancel_order', [$order, 'cancelOrder']);
        add_action('wp_ajax_pay_ticket', [$order, 'payTicket']);
        add_action('wp_ajax_create_ticket', [$order, 'createTicket']);
        add_action('wp_ajax_print_ticket', [$order, 'printTicket']);
        add_action('wp_ajax_get_balance', [$users, 'getBalance']);
        add_action('wp_ajax_insert_invoice_order', [$order, 'insertInvoiceOrder']);

        // Endereços
        add_action('wp_ajax_get_addresses', [$conf, 'getAddressShopping']);
        add_action('wp_ajax_set_address', [$conf, 'setAddressShopping']);

        // Agências Jadlog 
        add_action('wp_ajax_set_agency_jadlog', [$conf, 'setAgencyJadlog']);
        add_action('wp_ajax_get_agency_jadlog', [$conf, 'getAgencyJadlog']);

        // Minhas lojas
        add_action('wp_ajax_get_stores', [$conf, 'getStories']);
        add_action('wp_ajax_set_store', [$conf, 'setStore']);

        // Exibir calculadora na tela do produto
        add_action('wp_ajax_get_calculator_show', [$conf, 'get_calculator_show']);
        add_action('wp_ajax_set_calculator_show', [$conf, 'set_calculator_show']);

        // Cotação por embalagem
        add_action('wp_ajax_nopriv_cotation_product_page', [$cotacao, 'cotationProductPage']);
        add_action('wp_ajax_cotation_product_page', [$cotacao, 'cotationProductPage']);
        
        add_action('wp_ajax_update_order', [$cotacao, 'refreshCotation']);
        add_action('wp_ajax_update_order', [$order, 'removeOrder']);

        // Logs 
        add_action('wp_ajax_get_logs_melhorenvio_list', [$logs, 'index']);
        add_action('wp_ajax_detail_log_melhorenvio', [$logs, 'detail']);

        // Opçoes de transportadoras
        add_action('wp_ajax_save_options', [$conf, 'save']);
        add_action('wp_ajax_get_options', [$conf, 'getOptionsShipments']);

        // Pegar metodos de envios ativos
        add_action('wp_ajax_get_metodos', [$conf, 'getMethodsEnables']);
    }
    
    /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function init_classes() {

        if ( $this->is_request( 'admin' ) ) {
            $this->container['admin'] = new App\Admin();
        }

        if ( $this->is_request( 'frontend' ) ) {
            $this->container['frontend'] = new App\Frontend();
        }

        if ( $this->is_request( 'ajax' ) ) {
            // $this->container['ajax'] =  new App\Ajax();
        }

        if ( $this->is_request( 'rest' ) ) {
            $this->container['rest'] = new App\REST_API();
        }

        $this->container['assets'] = new App\Assets();
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'baseplugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * What type of request is this?
     *
     * @param  string $type admin, ajax, cron or frontend.
     *
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();

            case 'ajax' :
                return defined( 'DOING_AJAX' );

            case 'rest' :
                return defined( 'REST_REQUEST' );

            case 'cron' :
                return defined( 'DOING_CRON' );

            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

} // Base_Plugin

$baseplugin = Base_Plugin::init();
