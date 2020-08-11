<?php

require __DIR__ . '/vendor/autoload.php';

/*
Plugin Name: Melhor Envio v2
Plugin URI: https://melhorenvio.com.br
Description: Plugin para cotação e compra de fretes utilizando a API da Melhor Envio.
Version: 2.8.0
Author: Melhor Envio
Author URI: melhorenvio.com.br
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: baseplugin
Tested up to: 5.0
Requires PHP: 5.6
WC requires at least: 4.0
WC tested up to: 5.7
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
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__));
}

if (!file_exists(plugin_dir_path(__FILE__) . '/vendor/autoload.php')) {
    add_action('admin_notices', function () {
        echo sprintf('<div class="error">
            <p>%s</p>
        </div>', 'Erro ao ativar o plugin da Melhor Envio, não localizada a vendor do plugin');
    });
    return false;
}

use Controllers\WoocommerceCorreiosCalculoDeFreteNaPaginaDoProduto;
use Models\CalculatorShow;
use Models\Method;
use Services\RouterService;
use Services\SessionService;
use Services\ShippingMelhorEnvioService;
use Services\ShortCodeService;
use Services\TrackingService;

/**
 * Base_Plugin class
 *
 * @class Base_Plugin The class that holds the entire Base_Plugin plugin
 */
final class Base_Plugin
{

    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '2.8.0';

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
    public function __construct()
    {
        $this->define_constants();

        register_activation_hook(__FILE__, array($this, 'activate'));

        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init_plugin'), 9, false);

        (new SessionService())->clear();
    }

    /**
     * Initializes the Base_Plugin() class
     *
     * Checks for an existing Base_Plugin() instance
     * and if it doesn't find one, creates it.
     */
    public static function init()
    {
        static $instance = false;

        if (!$instance) {
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
        define('BASEPLUGIN_VERSION', $this->version);
        define('BASEPLUGIN_FILE', __FILE__);
        define('BASEPLUGIN_PATH', dirname(BASEPLUGIN_FILE));
        define('BASEPLUGIN_INCLUDES', BASEPLUGIN_PATH . '/includes');
        define('BASEPLUGIN_URL', plugins_url('', BASEPLUGIN_FILE));
        define('BASEPLUGIN_ASSETS', BASEPLUGIN_URL . '/assets');
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
            $pathPlugins = ABSPATH . 'wp-content/plugins';
        }

        $errorsPath = [];

        if (!is_dir($pathPlugins . '/woocommerce')) {
            $errorsPath[] = 'Defina o path do diretório de plugins nas configurações do plugin do Melhor Envio';
        }

        $errors = [];

        $pluginsActiveds = apply_filters('network_admin_active_plugins', get_option('active_plugins'));

        if (!class_exists('WooCommerce')) {
            $errors[] = 'Você precisa do plugin WooCommerce ativado no wordpress para utilizar o plugin do Melhor Envio';
        }

        if (!in_array('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php', $pluginsActiveds) && !is_multisite()) {
            $errors[] = 'Você precisa do plugin <a target="_blank" href="https://br.wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">WooCommerce checkout fields for Brazil</a> ativado no wordpress para utilizar o plugin do Melhor Envio';
        }

        if (!empty($errors)) {
            foreach ($errors as $err) {
                add_action('admin_notices', function () use ($err) {
                    echo sprintf('<div class="error">
                        <p>%s</p>
                    </div>', $err);
                });
            }
            return false;
        }

        if (empty($errorsPath)) {
            try {
                @include_once $pathPlugins . '/woocommerce/includes/class-woocommerce.php';
                include_once $pathPlugins . '/woocommerce/woocommerce.php';
                include_once $pathPlugins . '/woocommerce/includes/abstracts/abstract-wc-shipping-method.php';
            } catch (Exception $e) {
                add_action('admin_notices', function () {
                    echo sprintf('<div class="error">
                        <p>%s (%s)</p>
                    </div>', 'Erro ao incluir as classes do WooCommerce', $e->getMessage());
                });
                return false;
            }
        } else {
            add_action('admin_notices', function () {
                echo sprintf('<div class="error">
                    <p>%s</p>
                </div>', 'Verifique o caminho do diretório de plugins na página de configurações do plugin do Melhor Envio.');
            });
        }
    }

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate()
    {
        $installed = get_option('baseplugin_installed');

        if (!$installed) {
            update_option('baseplugin_installed', time());
        }

        update_option('baseplugin_version', BASEPLUGIN_VERSION);
    }

    /**
     * Include the required files
     *
     * @return void
     */
    public function includes()
    {
        try {
            require_once BASEPLUGIN_INCLUDES . '/class-assets.php';

            if ($this->is_request('admin')) {
                require_once BASEPLUGIN_INCLUDES . '/class-admin.php';
            }

            if ($this->is_request('frontend')) {
                require_once BASEPLUGIN_INCLUDES . '/class-frontend.php';
            }

            if ($this->is_request('rest')) {
                require_once BASEPLUGIN_INCLUDES . '/class-rest-api.php';
            }
        } catch (\Exception $e) {
            add_action('admin_notices', function () {
                echo sprintf('<div class="error">
                    <p>%s</p>
                </div>', $e->getMessage());
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

        (new TrackingService())->createTrackingColumnOrdersClient();

        $hideCalculator = (new CalculatorShow)->get();
        if ($hideCalculator) {
            $cotacaoProd = new WoocommerceCorreiosCalculoDeFreteNaPaginaDoProduto();
            $cotacaoProd->run();
        }

        add_action('init', array($this, 'init_classes'));
        add_action('init', array($this, 'localization_setup'));

        (new RouterService())->handler();

        require_once dirname(__FILE__) . '/services_methods/class-wc-melhor-envio-shipping.php';
        foreach (glob(plugin_dir_path(__FILE__) . 'services_methods/*.php') as $filename) {
            require_once $filename;
        }

        add_filter('woocommerce_shipping_methods', function ($methods) {
            $methods['melhorenvio_correios_pac']  = 'WC_Melhor_Envio_Shipping_Correios_Pac';
            $methods['melhorenvio_correios_sedex']  = 'WC_Melhor_Envio_Shipping_Correios_Sedex';
            $methods['melhorenvio_jadlog_package']  = 'WC_Melhor_Envio_Shipping_Jadlog_Package';
            $methods['melhorenvio_jadlog_com']  = 'WC_Melhor_Envio_Shipping_Jadlog_Com';
            $methods['melhorenvio_via_brasil_aero']  = 'WC_Melhor_Envio_Shipping_Via_Brasil_Aero';
            $methods['melhorenvio_via_brasil_rodoviario']  = 'WC_Melhor_Envio_Shipping_Via_Brasil_Rodoviario';
            $methods['melhorenvio_latam']  = 'WC_Melhor_Envio_Shipping_Latam';
            $methods['melhorenvio_correios_mini']  = 'WC_Melhor_Envio_Shipping_Correios_Mini';
            return $methods;
        });


        add_action('woocommerce_init', function () {
            $methods = (new ShippingMelhorEnvioService())
                ->getMethodsActivedsMelhorEnvio();

            if (count($methods) == 0) {
                add_action('admin_notices', function () {
                    echo sprintf('<div class="error">
                        <h2>Atenção usuário do Plugin Melhor Envio</h2>
                        <p>%s</p>
                    </div>', 'Por favor, verificar os métodos de envios do Melhor Envio na tela de <a href="/wp-admin/admin.php?page=wc-settings&tab=shipping">configurações de áreas de entregas do WooCommerce</a> após a instalação da versão <b>2.8.0</b>. Devido a nova funcionalidade de classes de entrega, é necessário selecionar novamente os métodos de envios do Melhor Envio.');
                });
            }
        });

        add_filter('woocommerce_package_rates', 'orderingQuotationsByPrice', 10, 2);

        function orderingQuotationsByPrice($rates, $package)
        {
            if (empty($rates)) return;
            if (!is_array($rates)) return;

            uasort($rates, function ($a, $b) {
                if ($a == $b) return 0;
                return ($a->cost < $b->cost) ? -1 : 1;
            });
            return $rates;
        }
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

            if ($this->is_request('frontend')) {
                $this->container['frontend'] = new App\Frontend();
            }

            if ($this->is_request('ajax')) {
                // $this->container['ajax'] =  new App\Ajax();
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
                echo sprintf('<div class="error">
                    <p>%s</p>
                </div>', $e->getMessage());
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
        load_plugin_textdomain('baseplugin', false, dirname(plugin_basename(__FILE__)) . '/languages/');
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
} // Base_Plugin

$baseplugin = Base_Plugin::init();
