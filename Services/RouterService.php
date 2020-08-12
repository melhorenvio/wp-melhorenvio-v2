<?php

namespace Services;

use Controllers\ConfigurationController;
use Controllers\LocationsController;
use Controllers\OrdersController;
use Controllers\QuotationController;
use Controllers\SessionsController;
use Controllers\StatusController;
use Controllers\TokenController;
use Controllers\UsersController;

/**
 * Class responsible for managing the routes of the plugin
 */
class RouterService
{
    public function handler()
    {
        $this->loadRoutesOrders();
        $this->loadRoutesUsers();
        $this->loadRoutesQuotations();
        $this->loadRoutesConfigurations();
        $this->loadRoutesStatus();
        $this->loadRoutesTokens();
        $this->loadRoutesTest();
        $this->loadRoutesSession();
        $this->loadRoutesLocation();
    }

    /**
     * function to start users routes
     *
     * @return void
     */
    private function loadRoutesUsers()
    {
        $usersController = new UsersController();

        add_action('wp_ajax_me', [$usersController, 'getMe']);
        add_action('wp_ajax_get_balance', [$usersController, 'getBalance']);
    }

    /**
     * function to start users routes
     *
     * @return void
     */
    private function loadRoutesOrders()
    {
        $ordersController = new OrdersController();

        add_action('wp_ajax_get_quotation', function () use ($ordersController) {
            $ordersController->getOrderQuotationByOrderId($_GET['id']);
        });
        add_action('wp_ajax_get_orders', [$ordersController, 'getOrders']);
        add_action('wp_ajax_add_cart', [$ordersController, 'addCart']);
        add_action('wp_ajax_add_order', [$ordersController, 'sendOrder']);
        add_action('wp_ajax_buy_click', [$ordersController, 'buyOnClick']);
        add_action('wp_ajax_remove_order', [$ordersController, 'removeOrder']);
        add_action('wp_ajax_cancel_order', [$ordersController, 'cancelOrder']);
        add_action('wp_ajax_pay_ticket', [$ordersController, 'payTicket']);
        add_action('wp_ajax_create_ticket', [$ordersController, 'createTicket']);
        add_action('wp_ajax_print_ticket', [$ordersController, 'printTicket']);
        add_action('wp_ajax_insert_invoice_order', [$ordersController, 'insertInvoiceOrder']);
    }

    /**
     * function to start quotations routes
     *
     * @return void
     */
    private function loadRoutesQuotations()
    {
        $quotationsController = new QuotationController();

        add_action('wp_ajax_nopriv_cotation_product_page', [$quotationsController, 'cotationProductPage']);
        add_action('wp_ajax_cotation_product_page', [$quotationsController, 'cotationProductPage']);
        add_action('wp_ajax_update_order', [$quotationsController, 'refreshCotation']);
    }

    /**
     * function to start configurations routes
     *
     * @return void
     */
    private function loadRoutesConfigurations()
    {
        $configurationsController = new ConfigurationController();

        add_action('wp_ajax_get_agency_jadlog', [$configurationsController, 'getAgencyJadlog']);
        add_action('wp_ajax_get_all_agencies_jadlog', [$configurationsController, 'getAgencyJadlog']);
        add_action('wp_ajax_get_configuracoes', [$configurationsController, 'getConfigurations']);
        add_action('wp_ajax_get_metodos', [$configurationsController, 'getMethodsEnables']);
        add_action('wp_ajax_save_configuracoes', [$configurationsController, 'saveAll']);
    }

    /**
     * function to start status routes
     *
     * @return void
     */
    private function loadRoutesStatus()
    {
        $statusController = new StatusController();

        add_action('wp_ajax_get_status_woocommerce', [$statusController, 'getStatus']);
    }

    /**
     * function to start tokens routes
     *
     * @return void
     */
    private function loadRoutesTokens()
    {
        $tokensController = new TokenController();

        add_action('wp_ajax_get_token', [$tokensController, 'getToken']);
        add_action('wp_ajax_save_token', [$tokensController, 'saveToken']);
        add_action('wp_ajax_verify_token', [$tokensController, 'verifyToken']);
    }

    /**
     * function to start tests routes
     *
     * @return void
     */
    private function loadRoutesTest()
    {
        add_action('wp_ajax_nopriv_environment', function () {
            (new TestService('2.8.0'))->run();
        });

        add_action('wp_ajax_environment', function () {
            (new TestService('2.8.0'))->run();
        });
    }

    /**
     * function to start session routes
     *
     * @return void
     */
    private function loadRoutesSession()
    {
        $sessionsController = new SessionsController();

        add_action('wp_ajax_delete_melhor_envio_session', [$sessionsController, 'deleteSession']);
        add_action('wp_ajax_get_melhor_envio_session', [$sessionsController, 'getSession']);
    }

    /**
     * function to start location routes
     *
     * @return void
     */
    private function loadRoutesLocation()
    {
        $locationController = new LocationsController();

        foreach (['wp_ajax_get_address', 'wp_ajax_nopriv_get_address'] as $action) {
            add_action($action, function () use ($locationController) {
                if (!isset($_GET['postal_code'])) {
                    return wp_send_json([
                        'error' => true,
                        'message' => 'Informar o campo "postal_code"'
                    ], 400);
                }
                return $locationController->getAddressByPostalCode($_GET['postal_code']);
            });
        }
    }
}
