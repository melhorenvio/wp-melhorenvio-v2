<?php
/*
Plugin Name: Melhor Envio v2
Plugin URI: https://melhorenvio.com.br
Description: Plugin para cotação e compra de fretes utilizando a API da Melhor Envio.
Version: 2.5.15
Author: Melhor Envio
Author URI: melhorenvio.com.br
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: baseplugin
Tested up to: 5.0
Requires PHP: 5.0
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
if ( !defined( 'ABSPATH' ) ) {
    define('ABSPATH', dirname(__FILE__));
}

if ( !file_exists(plugin_dir_path( __FILE__ ) . '/vendor/autoload.php')) {
    add_action( 'admin_notices', function(){
        echo sprintf('<div class="error">
            <p>%s</p>
        </div>', 'Erro ao ativar o plugin da Melhor Envio, não localizada a vendor do plugin');
    });
    return false;
}


use Controllers\OrdersController;
use Controllers\ConfigurationController;
use Controllers\TokenController;
use Controllers\PackageController;
use Controllers\UsersController;
use Controllers\CotationController;
use Controllers\WoocommerceCorreiosCalculoDeFreteNaPaginaDoProduto;
use Controllers\LogsController;
use Controllers\OptionsController;
use Controllers\StatusController;
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
    public $version = '2.5.15';

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

        session_start();

        require __DIR__ . '/vendor/autoload.php';
        
        $this->define_constants();

        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init_plugin' ), 9, false );

        function my_plugin_load_plugin_textdomain() {
            load_plugin_textdomain( 'melhor-envio', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
        }

        add_action( 'plugins_loaded', 'my_plugin_load_plugin_textdomain' );

        self::clearCotationSession();
    }

    public function clearCotationSession()
    {   
        $codeStore = md5(get_option('home'));

        $dateNow = date("Y-m-d h:i:s");

        if(isset($_SESSION[$codeStore]['cotations'])) {

            foreach ($_SESSION[$codeStore]['cotations'] as $key => $cotation) {

                if( !isset($cotation['created'])) {
                    unset($_SESSION[$codeStore]['cotations'][$key]);
                }

                if(date('Y-m-d H:i:s', strtotime('+2 hours', strtotime($cotation['created']))) < $dateNow) {
                    unset($_SESSION[$codeStore]['cotations'][$key]);
                }
            }
        }
    }

    public function loadMelhorEnvio()
    {        

        if (isset($_GET) && $_GET['page_id'] == get_option( 'woocommerce_cart_page_id' )) {
            return true;
        }

        if (is_admin()) {
            return true;
        }

        if(isset($_POST['woocommerce-shipping-calculator-nonce'])) {
            return true;
        }

        if (isset($_POST['shipping_method'])) {
            return true;
        }

        return false;
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

        // if (!isset($_SESSION[md5(get_option('home'))]['melhorenvio_token'])) {
        //     add_action( 'admin_notices', function() {
        //         echo sprintf('<div class="error">
        //             <p>%s</p>
        //         </div>', 'Por favor, informar seu token Melhor Envio');
        //     });
        //     return;
        // }
        
        $pathPlugins = get_option('melhor_envio_path_plugins');
        if(!$pathPlugins) {
            $pathPlugins = ABSPATH . 'wp-content/plugins';
        }

        $errorsPath = [];

        if (!file_exists($pathPlugins . '/woocommerce/includes/abstracts/abstract-wc-shipping-method.php')) {
            $errorsPath[] = 'Defina o path do diretório de plugins nas configurações do plugin do Melhor Envio';
        }

        if (!is_dir($pathPlugins . '/woocommerce')) {
            $errorsPath[] = 'Defina o path do diretório de plugins nas configurações do plugin do Melhor Envio';
        }

        $errors = [];

        $pluginsActiveds = apply_filters( 'network_admin_active_plugins', get_option( 'active_plugins' ));

        if (!class_exists('WooCommerce')) {
            $errors[] = 'Você precisa do plugin WooCommerce ativado no wordpress para utilizar o plugin do Melhor Envio';
        }

        if (!in_array('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php', $pluginsActiveds) && !is_multisite()) {
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

        if (empty($errorsPath)) {

            try {

                @include_once $pathPlugins . '/woocommerce/includes/class-woocommerce.php';
                include_once $pathPlugins . '/woocommerce/woocommerce.php';
                include_once $pathPlugins . '/woocommerce/includes/abstracts/abstract-wc-shipping-method.php';

                // Create the methods shippings
                foreach ( glob( plugin_dir_path( __FILE__ ) . 'services/*.php' ) as $filename ) {
                    include_once $filename;
                }

            } catch (Exception $e) {
                add_action( 'admin_notices', function() {
                    echo sprintf('<div class="error">
                        <p>%s (%s)</p>
                    </div>', 'Erro ao incluir as classes do WooCommerce', $e->getMessage());
                });
                return false;
            }
        } else {
            add_action( 'admin_notices', function() {
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

        try {

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

        } catch (Exception $e) {
            add_action( 'admin_notices', function() {
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
    public function init_hooks() {
        
        $token   = new tokenController();
        $order   = new OrdersController(); 
        $users   = new UsersController();
        $conf    = new ConfigurationController();
        $cotacao = new CotationController();
        $logs    = new LogsController();
        $options = new OptionsController();
        $status  = new StatusController();
        
        add_action( 'init', array( $this, 'init_classes' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );

        add_action('wp_ajax_get_orders', function() {
            $order = new OrdersController();
            echo $order->getOrders();
            die;
        });


        $hideCalculator = (new CalculatorShow)->get();

        if ($hideCalculator) {
            $cotacaoProd = new WoocommerceCorreiosCalculoDeFreteNaPaginaDoProduto();
            $cotacaoProd->run();
        }


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
        add_action('wp_ajax_get_agency_jadlog', [$conf, 'getAgencyJadlog']);
        add_action('wp_ajax_nopriv_cotation_product_page', [$cotacao, 'cotationProductPage']);
        add_action('wp_ajax_cotation_product_page', [$cotacao, 'cotationProductPage']);
        add_action('wp_ajax_update_order', [$cotacao, 'refreshCotation']);
        add_action('wp_ajax_get_info_melhor_envio', function() {

            if (!isset($_GET['cep'])) {
                echo json_encode([
                    'error' => 'Informar o cep de destino'
                ]);
                die;
            }
    
            $response['cep_destiny'] = $_GET['cep'];
    
            $params = array(
                'headers'=> array(
                    'Content-Type' => 'application/json',
                    'Accept'=>'application/json',
                    'Authorization' => 'Bearer '.$response['token']
                )
            );

    
            $response['package'] = [
                'width'  => (isset($_GET['width']))  ? (float) $_GET['width']  : 17 ,
                'height' => (isset($_GET['height'])) ? (float) $_GET['height'] : 23,
                'length' => (isset($_GET['length'])) ? (float) $_GET['length'] : 10,
                'weight' => (isset($_GET['weight'])) ? (float) $_GET['weight'] : 1
            ];
    
    
            $options['insurance_value'] = (isset($_GET['insurance_value']))  ? (float) $_GET['insurance_value']  : 20.50;
    
            $response['insurance_value'] = (isset($_GET['insurance_value']))  ? (float) $_GET['insurance_value']  : 20.50;

            if (isset($_GET['path_test_plugin'])) {
                $response['path_test_plugin'] = str_replace('|', '/', $_GET['path_test_plugin']);
            }
    
            // $response['cotation'] = (new CotationController())->makeCotationPackage($response['package'], [1,2,3,4,9], $response['cep_destiny'], $options);

            // $response['enableds'] = (new Method())->getArrayShippingMethodsEnabledByZoneMelhorEnvio();
    
            $response['plugins_instaled'] = apply_filters( 'network_admin_active_plugins', get_option( 'active_plugins' ));
    
            $response['is_multisite'] = is_multisite();

            $response['pathPlugins'] = $pathPlugins;
        
            $response['path'] = plugin_dir_path( __FILE__ );

            $response['pathAlternative'] = $pathPlugins;

            $pathPlugins = get_option('melhor_envio_path_plugins');
            if (!$pathPlugins) {
                $pathPlugins = ABSPATH . 'wp-content/plugins';
            }
    
            foreach ( glob( $response['pathAlternative'] . $this->version . '/services/*.php' ) as $filename ) {
                $response['servicesFile'][] = $filename;
            }

            foreach ( glob( $response['pathAlternative'] . '/2.5.0/services/*.php' ) as $filename ) {
                $response['servicesFile'][] = $filename;
            }

            foreach ( glob( $response['pathAlternative'] . '/melhor-envio-cotacao/services/*.php' ) as $filename ) {
                $response['servicesFile'][] = $filename;
            }

            foreach ( glob( $pathPlugins . 'services/*.php' ) as $filename ) {
                $response['servicesFile'][] = $filename;
            }

            $response['version'] = $this->version;

            $response['session'] = $_SESSION;

            $response['user']   = (new UsersController())->getInfo();
    
            $response['origem'] = (new UsersController())->getFrom();

            $response['token'] = get_option('wpmelhorenvio_token');

            $response['account'] = wp_remote_retrieve_body(
                wp_remote_get('https://api.melhorenvio.com/v2/me', $params)
            );
    
            echo json_encode($response);
            die;
        });

        add_action('wp_ajax_check_path', function() {

            $data['version'] = $this->version;

            $data['home'] = get_home_path(__FILE__);

            $data['plugin_dir_path'] = dirname( __FILE__ );
            
            $pathPlugins = get_option('melhor_envio_path_plugins');
            if (!$pathPlugins) {
                $pathPlugins = ABSPATH . 'wp-content/plugins';
            }

            $data['path_plugins'] = $pathPlugins;

            if (isset($_GET['path'])) {
                $data['path_test'] = str_replace('%', '/', $_GET['path']);
            }

            foreach ( glob( $data['path_plugins'] . '/' . $this->version . '/services/*.php' ) as $filename ) {
                $data['services_file']['current_version_' . $this->version][] = $filename;
            }

            foreach ( glob( $data['path_plugins'] . '/2.5.0/services/*.php' ) as $filename ) {
                $data['services_file']['fixed-2.5.0'][] = $filename;
            }

            foreach ( glob( $data['path_plugins'] . '/melhor-envio-cotacao/services/*.php' ) as $filename ) {
                $data['services_file']['producao'][] = $filename;
            }

            foreach ( glob( $data['path_test'] . '/services/*.php' ) as $filename ) {
                $data['services_file']['test'][] = $filename;
            }

            echo json_encode($data);
            die;
        });

        // Todas as configurações
        add_action('wp_ajax_get_configuracoes', function(){

            $data = [
                'addresses'        => (new Models\Address())->getAddressesShopping()['addresses'],
                'stores'           => (new Models\Store())->getStories()['stores'],
                'agencies'         => (new Models\Agency())->getAgencies()['agencies'],
                'calculator'       => (new Models\CalculatorShow())->get(),
                'use_insurance'    => (new Models\UseInsurance())->get(),
                'where_calculator' => (!get_option('melhor_envio_option_where_show_calculator')) ? 'woocommerce_before_add_to_cart_button' : get_option('melhor_envio_option_where_show_calculator'),
                'metodos'          => (new Controllers\ConfigurationController())->getMethodsEnablesArray(),
                'style_calculator' => (new Controllers\ConfigurationController())->getStyleArray(),
                'path_plugins'     => (new Controllers\ConfigurationController())->getPathPluginsArray(),
                'options_calculator' => (new Controllers\ConfigurationController())->getOptionsCalculator() 
            ];

            echo json_encode($data);
            die;
        });

        // Salvar as confifurações
        add_action('wp_ajax_save_configuracoes', function() {
            echo json_encode((new Controllers\ConfigurationController())->saveAll($_POST));
            die;
        });

        // Logs 
        add_action('wp_ajax_get_logs_melhorenvio_list', [$logs, 'indexResponse']);
        add_action('wp_ajax_detail_log_melhorenvio', [$logs, 'detailResponse']);

        add_action('wp_ajax_get_metodos', [$conf, 'getMethodsEnables']);

        // Status WooCommerce
        add_action('wp_ajax_get_status_woocommerce', [$status, 'getStatus']);

        add_action('wp_ajax_delete_melhor_envio_session', function(){

            $codeStore = md5(get_option('home'));

            delete_option('melhorenvio_user_info');

            unset($_SESSION[$codeStore]['cotations']);
            unset($_SESSION[$codeStore]['melhorenvio_token']);

            unset($_SESSION[$codeStore]['melhorenvio_user_info']);
        
            unset($_SESSION[$codeStore]['melhorenvio_address_selected_v2']);
            unset($_SESSION[$codeStore]['melhorenvio_address']);
            
            unset($_SESSION[$codeStore]['melhorenvio_stores']);
            unset($_SESSION[$codeStore]['melhorenvio_store_v2']);
        
            unset($_SESSION[$codeStore]['melhorenvio_options']);
            echo json_encode($_SESSION);
            die;
        });

        add_action('wp_ajax_get_melhor_envio_session', function(){
            echo json_encode($_SESSION);
            die;
        });

        add_action('wp_ajax_get_logs_order', [$logs, 'getLogsOrder']);

        add_action('wp_ajax_verify_token', function() {
            if (!get_option('wpmelhorenvio_token')) {
                echo json_encode(['exists_token' => false]);
                die;
            }
            echo json_encode(['exists_token' => true]);
            die;
        });
    }
    
    /**
     * Instantiate the required classes
     *
     * @return void
     */
    public function init_classes() {

        try {
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
            
        } catch (Exception $e) {

            add_action( 'admin_notices', function() {
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
