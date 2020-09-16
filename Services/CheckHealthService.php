<?php

namespace Services;

use Helpers\NoticeHelper;

/**
 * Health service class
 */
class CheckHealthService
{
    public function init()
    {
        $this->hasShippingMethodsMelhorEnvio();
        $this->hasToken();
        $this->noticesSessions();
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

                NoticeHelper::addNotice(
                    $message,
                    NoticeHelper::NOTICE_INFO
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
            NoticeHelper::addNotice($message, NoticeHelper::NOTICE_INFO);
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
                NoticeHelper::addNotice($err, NoticeHelper::NOTICE_INFO);
            }
        }

        return [
            'errors' => $errors,
            'errorsPath' => $errorsPath
        ];
    }

    /**
     * function to check has notices in sessions.
     *
     * @return void
     */
    public function noticesSessions()
    {
        $notices = (new SessionNoticeService())->get();

        $notices = array_map(function ($notice) {
            return $notice['notice'];
        }, $notices);

        $notices = array_unique($notices);

        if (!empty($notices)) {
            foreach ($notices as $notice) {
                NoticeHelper::addNotice(
                    $notice,
                    NoticeHelper::NOTICE_INFO
                );
            }
        }
    }
}
