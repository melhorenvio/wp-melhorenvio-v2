<?php

namespace Services;

use Helpers\NoticeHelper;

class ListPluginsIncompatiblesService
{
    const PLUGINS_INCOMPATIBLES = [
        'wpc-composite-products/wpc-composite-products.php'
    ];

    /**
     * Function to init a seach by plugins incompatibles.
     *
     * @return void
     */
    public function init()
    {
        $installed = $this->getListPluginsInstalled();

        if (empty($installed) || empty(self::PLUGINS_INCOMPATIBLES)) {
            return false;
        }

        foreach ($installed as $plugin) {
            if (in_array($plugin,self::PLUGINS_INCOMPATIBLES)) {
                NoticeHelper::addNotice(
                    sprintf("O plugin <b>%s</b> pode ser incompatível com o plugin do Melhor Envio.", $plugin),
                    NoticeHelper::NOTICE_INFO
                );
            }
        }
    }

    /**
     * Function to return a list with plugins installed in WP.
     *
     * @return array
     */
    public function getListPluginsInstalled()
    {
        return apply_filters(
            'network_admin_active_plugins',
            get_option('active_plugins')
        );
    }
}