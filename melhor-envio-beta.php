<?php
/*
Plugin Name: Melhor Envio
Plugin URI: https://melhorenvio.com.br
Description: Plugin para cotação e compra de fretes utilizando a API da Melhor Envio.
Version: 2.11.31
Author: Melhor Envio
Author URI: melhorenvio.com.br
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: melhor-envio
Tested up to: 6.0
Requires PHP: 7.2
WC requires at least: 4.0
WC tested up to: 6.2
Domain Path: /languages
*/

/**
 * Copyright (c) 2022 Melhor Envio. All rights reserved.
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
if (!defined('ABSPATH')) exit;

// check if the composer packages are installed
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    add_action( 'admin_notices', function () {
        $class = 'notice notice-error';
        $message = 'Erro ao ativar o plugin da Melhor Envio: a pasta <code>vendor</code> não foi localizada no plugin.';
        printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
    } );
    return false;
}

require_once __DIR__ . '/vendor/autoload.php';

use MelhorEnvio\Controllers\ShowCalculatorProductPage;
use MelhorEnvio\Models\CalculatorShow;
use MelhorEnvio\Models\Version;
use MelhorEnvio\Services\CheckHealthService;
use MelhorEnvio\Services\ClearDataStored;
use MelhorEnvio\Services\RolesService;
use MelhorEnvio\Services\RouterService;
use MelhorEnvio\Services\ShortCodeService;
use MelhorEnvio\Services\TrackingService;
use MelhorEnvio\Services\ListPluginsIncompatiblesService;
use MelhorEnvio\Services\SessionNoticeService;
use MelhorEnvio\Helpers\SessionHelper;
use MelhorEnvio\Helpers\EscapeAllowedTags;

/**
 * Melhor_Envio_Plugin class
 *
 * @class Melhor_Envio_Plugin The class that starts the plugin
 */
final class Melhor_Envio_Plugin
{
    /**
     * Plugin version
     *
     * @var string
     */
    public $version;

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = array();

    /**
     * Constructor for the Melhor_Envio_Plugin class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    public function __construct()
    {
        $this->version = Version::VERSION;

        $this->define_constants();

        register_activation_hook(__FILE__, array($this, 'activate'));

        add_action('plugins_loaded', array($this, 'init_plugin'), 9, false);
    }

    /**
     * Initializes the Melhor_Envio_Plugin() class
     *
     * Checks for an existing Melhor_Envio_Plugin() instance
     * and if it doesn't find one, creates it.
     */
    public static function init()
    {

        static $instance = false;

        if (!$instance) {
            $instance = new self();
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
    public function __get($prop)
    {
        if (array_key_exists($prop, $this->container)) {
            return $this->container[$prop];
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
    public function __isset($prop)
    {
        return isset($this->{$prop}) || isset($this->container[$prop]);
    }

    /**
     * Define the constants
     *
     * @return void
     */
    public function define_constants()
    {
        define('MELHORENVIO_VERSION', $this->version);
        define('MELHORENVIO_FILE', __FILE__);
        define('MELHORENVIO_PATH', dirname(MELHORENVIO_FILE));
        define('MELHORENVIO_INCLUDES', MELHORENVIO_PATH . '/includes');
        define('MELHORENVIO_URL', plugins_url('', MELHORENVIO_FILE));
        define('MELHORENVIO_ASSETS', MELHORENVIO_URL . '/assets');
    }

    /**
     * Load the plugin after all plugis are loaded
     *
     * @return void
     */
    public function init_plugin()
    {
        $this->includes();
        $this->init_hooks();

        $pathPlugins = get_option('melhor_envio_path_plugins');
        if (!$pathPlugins) {
            $pathPlugins =  WP_PLUGIN_DIR;
        }

        if (is_admin()) {
            (new SessionNoticeService())->showNotices();
            $result = (new CheckHealthService())->checkPathPlugin($pathPlugins);

            if (!empty($result['errors'])) {
                return false;
            }
        }
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate()
    {
        $installed = get_option('melhorenvio_installed');

        if (!$installed) {
            update_option('melhorenvio_installed', time());
        }

        update_option('melhorenvio_version', MELHORENVIO_VERSION);

        (new ClearDataStored())->clear();
    }

    /**
     * Include the required files
     *
     * @return void
     */
    public function includes()
    {
        try {
            require_once MELHORENVIO_INCLUDES . '/class-assets.php';

            if ($this->is_request('admin')) {
                require_once MELHORENVIO_INCLUDES . '/class-admin.php';
            }

            if ($this->is_request('frontend')) {
                require_once MELHORENVIO_INCLUDES . '/class-frontend.php';
            }

            if ($this->is_request('rest')) {
                require_once MELHORENVIO_INCLUDES . '/class-rest-api.php';
            }
        } catch (\Exception $e) {
            add_action('admin_notices', function ($e) {
                echo wp_kses(sprintf('<div class="error">
                    <p>%s</p>
                </div>', $e->getMessage()), EscapeAllowedTags::allow_tags(["div", "p"]));
            });
            return false;
        }
    }

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function init_hooks()
    {
        if (is_admin()) {
            (new CheckHealthService())->init();
            (new RolesService())->init();
        }

        add_action('init', array($this, 'init_classes'));
        add_action('init', array($this, 'localization_setup'));

        (new RouterService())->handler();

        require_once dirname(__FILE__) . '/services_methods/class-wc-melhor-envio-shipping.php';
        foreach (glob(plugin_dir_path(__FILE__) . 'services_methods/*.php') as $filename) {
            require_once $filename;
        }

        (new TrackingService())->createTrackingColumnOrdersClient();
        $hideCalculator = (new CalculatorShow)->get();
        if ($hideCalculator) {
            (new ShowCalculatorProductPage())->insertCalculator();
        }

        add_filter( 'safe_style_css', function( $styles ) {
            $styles[] = 'display';
            return $styles;
        } );

        add_filter('woocommerce_shipping_methods', function ($methods) {
            $methods['melhorenvio_correios_pac']  = 'WC_Melhor_Envio_Shipping_Correios_Pac';
            $methods['melhorenvio_correios_sedex']  = 'WC_Melhor_Envio_Shipping_Correios_Sedex';
            $methods['melhorenvio_jadlog_package']  = 'WC_Melhor_Envio_Shipping_Jadlog_Package';
            $methods['melhorenvio_jadlog_com']  = 'WC_Melhor_Envio_Shipping_Jadlog_Com';
            $methods['melhorenvio_via_brasil_rodoviario']  = 'WC_Melhor_Envio_Shipping_Via_Brasil_Rodoviario';
            $methods['melhorenvio_latam_juntos']  = 'WC_Melhor_Envio_Shipping_Latam_Juntos';
            $methods['melhorenvio_azul_amanha']  = 'WC_Melhor_Envio_Shipping_Azul_Amanha';
            $methods['melhorenvio_azul_ecommerce']  = 'WC_Melhor_Envio_Shipping_Azul_Ecommerce';
            $methods['melhorenvio_correios_mini']  = 'WC_Melhor_Envio_Shipping_Correios_Mini';
            $methods['melhorenvio_buslog_rodoviario']  = 'WC_Melhor_Envio_Shipping_Buslog_Rodoviario';
            return $methods;
        });

        add_filter('woocommerce_package_rates', 'orderingQuotationsByPrice', 10, 2);
        function orderingQuotationsByPrice($rates, $package)
        {
            uasort($rates, function ($a, $b) {
                if ($a == $b) return 0;
                return ($a->cost < $b->cost) ? -1 : 1;
            });
            return $rates;
        }

        add_action('upgrader_process_complete', function () {
            (new ClearDataStored())->clear();
        });

        if (is_admin()) {
            (new ListPluginsIncompatiblesService())->init();
        }

        function load_var_nonce()
        {
            $wpApiSettings = json_encode( array(
                'nonce_configs' => wp_create_nonce( 'save_configurations' ),
                'nonce_orders' => wp_create_nonce( 'orders' ),
                'nonce_tokens' => wp_create_nonce( 'tokens' ),
                'nonce_users' => wp_create_nonce( 'users' ),
            ) );

            wp_register_script( 'wp-nonce-melhor-evio-wp-api', '' );
            wp_enqueue_script( 'wp-nonce-melhor-evio-wp-api' );
            wp_add_inline_script( 'wp-nonce-melhor-evio-wp-api', "var wpApiSettingsMelhorEnvio = ${wpApiSettings};" );
        }

        add_action( 'admin_enqueue_scripts', 'load_var_nonce');
        add_action( 'wp_enqueue_scripts', 'load_var_nonce');
    }

    /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function init_classes()
    {
        try {
            if ($this->is_request('admin')) {
                $this->container['admin'] = new App\Admin();
            }

            if ($this->is_request('rest')) {
                $this->container['rest'] = new App\REST_API();
            }

            add_shortcode('calculadora_melhor_envio', function ($attr) {
                if (isset($attr['product_id'])) {
                    $product = wc_get_product($attr['product_id']);
                    if ($product) {
                        (new ShortCodeService($product))->shortcode();
                    }
                }
            });

            $this->container['assets'] = new App\Assets();
        } catch (\Exception $e) {
            add_action('admin_notices', function () use ($e) {
                echo wp_kses(
                    sprintf('<div class="error">
                    <p>%s</p>
                </div>', $e->getMessage()),
                    EscapeAllowedTags::allow_tags(["div", "p"])
                );
            });

            return false;
        }
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup()
    {
        load_plugin_textdomain('melhor-envio', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    /**
     * What type of request is this?
     *
     * @param  string $type admin, ajax, cron or frontend.
     *
     * @return bool
     */
    private function is_request($type)
    {
        switch ($type) {
            case 'admin':
                return is_admin();

            case 'ajax':
                return defined('DOING_AJAX');

            case 'rest':
                return defined('REST_REQUEST');

            case 'cron':
                return defined('DOING_CRON');

            case 'frontend':
                return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON');
        }
    }
} // Melhor_Envio_Plugin

Melhor_Envio_Plugin::init();
