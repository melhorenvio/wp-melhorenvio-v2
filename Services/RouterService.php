<?php

namespace Services;

use Controllers\AgenciesController;
use Controllers\ConfigurationController;
use Controllers\LocationsController;
use Controllers\OrdersController;
use Controllers\QuotationController;
use Controllers\SessionsController;
use Controllers\StatusController;
use Controllers\TokenController;
use Controllers\UsersController;
use Controllers\PathController;
use Controllers\PayloadsController;
use Controllers\CartController;
use Controllers\NoticeFormController;
use Controllers\RequestsController;
use Models\Version;

/**
 * Class responsible for managing the routes of the plugin
 */
class RouterService
{

    const MESSAGE_ERROR_NOT_POST_ID = 'Informar o campo "post_id"';

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
        $this->loadRoutesPath();
        $this->laodRoutesPayload();
        $this->loadRoutesNotices();
        $this->loadRoutesTestUserWooCommerceData();
        $this->loadRouteDataUser();
        $this->loadRouteCart();
        $this->loadRouteForm();
        $this->loadRequestController();
        $this->loadRoutesAgencies();
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

        add_action('wp_ajax_get_token', [$tokensController, 'get']);
        add_action('wp_ajax_save_token', [$tokensController, 'save']);
        add_action('wp_ajax_verify_token', [$tokensController, 'verifyToken']);
    }

    /**
     * function to start tests routes
     *
     * @return void
     */
    private function loadRoutesTest()
    {
        $version = Version::VERSION;

        add_action('wp_ajax_nopriv_environment', function () use ($version) {
            (new TestService($version))->run();
        });

        add_action('wp_ajax_environment', function () use ($version) {
            (new TestService($version))->run();
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
                if (empty($_GET['postal_code'])) {
                    return wp_send_json([
                        'error' => true,
                        'message' => self::MESSAGE_ERROR_NOT_POST_ID
                    ], 400);
                }
                return $locationController->getAddressByPostalCode($_GET['postal_code']);
            });
        }
    }

    /**
     * function to start path routes
     *
     * @return void
     */
    private function loadRoutesPath()
    {
        $pathController = new PathController();

        add_action('wp_ajax_check_path', [$pathController, 'getPathPlugin']);
    }

    /**
     * function to start payload routes
     *
     * @return void
     */
    private function laodRoutesPayload()
    {
        $payloadsController = new PayloadsController();

        add_action('wp_ajax_nopriv_get_payload', function () use ($payloadsController) {
            if (empty($_GET['post_id'])) {
                return wp_send_json([
                    'error' => true,
                    'message' => self::MESSAGE_ERROR_NOT_POST_ID
                ], 400);
            }
            return $payloadsController->show($_GET['post_id']);
        });

        add_action('wp_ajax_get_payload', function () use ($payloadsController) {
            if (empty($_GET['post_id'])) {
                return wp_send_json([
                    'error' => true,
                    'message' => self::MESSAGE_ERROR_NOT_POST_ID
                ], 400);
            }
            return $payloadsController->showLogged($_GET['post_id']);
        });

        add_action('wp_ajax_destroy_payload', function () use ($payloadsController) {
            if (empty($_GET['post_id'])) {
                return wp_send_json([
                    'error' => true,
                    'message' => self::MESSAGE_ERROR_NOT_POST_ID
                ], 400);
            }
            return $payloadsController->destroy($_GET['post_id']);
        });

        add_action('wp_ajax_get_payload_cart', function () use ($payloadsController) {
            if (empty($_GET['post_id'])) {
                return wp_send_json([
                    'error' => true,
                    'message' => self::MESSAGE_ERROR_NOT_POST_ID
                ], 400);
            }

            if (empty($_GET['service'])) {
                return wp_send_json([
                    'error' => true,
                    'message' => 'Informar o campo "service"'
                ], 400);
            }
            
            return $payloadsController->showPayloadCart($_GET['post_id'], $_GET['service']);
        });
    }

    /*
     * function to start path notices
     *
     * @return void
     */
    public function loadRoutesNotices()
    {
        add_action('wp_ajax_get_notices', function () {
            (new SessionNoticeService())->get();
        });

        add_action('wp_ajax_remove_notices', function () {
            (new SessionNoticeService())->remove($_GET['id']);
        });
    }

    public function loadRoutesTestUserWooCommerceData()
    {
        $locationService = new LocationService();

        add_action('wp_ajax_test_user_woocommerce_data', function () use ($locationService) {

            if (empty($_GET['postcode'])) {
                return wp_send_json([
                    'message' => 'Informar o parametro "postcode"'
                ]);
            }

            $address = $locationService->getAddressByPostalCode($_GET['postcode']);

            $userData = (new UserWooCommerceDataService())->set($address, true);

            return wp_send_json($userData);
        });
    }

    /*
     * function to start user data routes
     *
     * @return void
     */
    public function loadRouteDataUser()
    {
        $usersController = new UsersController();

        add_action('wp_ajax_user_woocommerce_data', function () use ($usersController) {
            return wp_send_json([
                'data' => $usersController->getFrom()
            ]);
        });
    }

    public function loadRouteCart()
    {
        $cartController = new CartController();

        add_action('wp_ajax_show_cart', function () use ($cartController) {
            return wp_send_json([
                'data' => $cartController->getInfoCart()
            ]);
        });
    }

    /*
     * function to start form routes
     *
     * @return void
     */
    public function loadRouteForm()
    {
        $formController = new NoticeFormController();

        add_action('wp_ajax_open_form_melhor_envio', function () use ($formController) {
            return wp_send_json($formController->openForm());
        });

        add_action('wp_ajax_show_form_melhor_envio', function () use ($formController) {
            return wp_send_json($formController->showForm());
        });

        add_action('wp_ajax_hide_form_melhor_envio', function () use ($formController) {
            return wp_send_json($formController->hideForm());
        });
    }

    /*
     * function to start requests routes
     *
     * @return void
     */
    public function loadRequestController()
    {
        $requestsController = new RequestsController;

        add_action('wp_ajax_logs_requests', function () use ($requestsController) {
           return $requestsController->getLogs();
        });

        add_action('wp_ajax_delete_logs_requests', function () use ($requestsController) {
            return $requestsController->deleteLogs();
        });
    }

    /*
     * function to start agencies routes
     *
     * @return json
     */
    public function loadRoutesAgencies()
    {
        $agenciesController = new AgenciesController();
        add_action('wp_ajax_get_agencies', function () use ($agenciesController) {
            return $agenciesController->get();
        });
    }
}
