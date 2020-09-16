<?php

namespace Services;

/**
 * Health service class
 */
class CheckHealthService
{
    protected $serviceNotice;

    public function __construct()
    {
        $this->serviceNotice = new NoticeService();
    }

    public function init()
    {
        $this->hasShippingMethodsMelhorEnvio();
        $this->hasToken();
    }

    /**
     * Function to display message to the user if he does not 
     * have selected shipping methods
     *
     * @return void
     */
    public function hasShippingMethodsMelhorEnvio()
    {
        add_action('woocommerce_init', function () {
            $methods = (new ShippingMelhorEnvioService())
                ->getMethodsActivedsMelhorEnvio();

            if (count($methods) == 0) {
                $message = sprintf('<div class="error">
                    <h2>Atenção usuário do Plugin Melhor Envio</h2>
                        <p>%s</p>
                    </div>', 'Por favor, verificar os métodos de envios do Melhor Envio na tela de <a href="/wp-admin/admin.php?page=wc-settings&tab=shipping">configurações de áreas de entregas do WooCommerce</a> após a instalação da versão <b>2.8.0</b>. Devido a nova funcionalidade de classes de entrega, é necessário selecionar novamente os métodos de envios do Melhor Envio.');

                $this->serviceNotice->addNotice(
                    $message,
                    $this->serviceNotice::NOTICE_INFO
                );
            }
        });
    }

    /**
     * Function to display alert to the user if there is no saved token
     *
     * @return void
     */
    public function hasToken()
    {
        $token = (new TokenService())->get();
        if (!$token) {
            $message = 'Atenção! você não possui um token Melhor Envio cadastrado, acesse a plataforma do <a target="_blank" href="https://melhorenvio.com.br/painel/gerenciar/tokens">Melhor Envio</a> e gere seu token de acesso';
            $this->serviceNotice->addNotice($message, $this->serviceNotice::NOTICE_INFO);
        }
    }

    /**
     * Function to check if the plugin has all the necessary plugins to run.
     *
     * @param string $pathPlugins
     * @return array
     */
    public function checkPathPlugin($pathPlugins)
    {
        $errorsPath = [];
        if (!is_dir($pathPlugins . '/woocommerce')) {
            $errorsPath[] = 'Defina o path do diretório de plugins nas configurações do plugin do Melhor Envio';
        }

        $errors = [];
        $pluginsActiveds = apply_filters(
            'network_admin_active_plugins',
            get_option('active_plugins')
        );

        if (!class_exists('WooCommerce')) {
            $errors[] = 'Você precisa do plugin WooCommerce ativado no wordpress para utilizar o plugin do Melhor Envio';
        }

        if (!in_array('woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php', $pluginsActiveds) && !is_multisite()) {
            $errors[] = 'Você precisa do plugin <a target="_blank" href="https://br.wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/">WooCommerce checkout fields for Brazil</a> ativado no wordpress para utilizar o plugin do Melhor Envio';
        }

        if (!empty($errors)) {
            foreach ($errors as $err) {
                (new NoticeService())->addNotice($err, 'notice-error');
            }
        }

        return [
            'errors' => $errors,
            'errorsPath' => $errorsPath
        ];
    }
}
