<?php

namespace Services;

use Helpers\NoticeHelper;

class ListPluginsIncompatiblesService
{
    const URL_PLUGINS_INCOMPATIBLES = 'https://wordpress-plugin.s3.us-east-2.amazonaws.com/plugins-incompatible.json';

    /**
     * Function to init a seach by plugins incompatibles.
     *
     * @return void
     */
    public function init()
    {
        $installed = $this->getListPluginsInstalled();

        $incompatibles = $this->getListPluginsIncompatibles();

        if (empty($installed) || empty($incompatibles)) {
            return false;
        }

        foreach ($installed as $plugin) {
            if (in_array($plugin, $incompatibles)) {
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

    public function getListPluginsIncompatibles()
    {
        return json_decode(file_get_contents(self::URL_PLUGINS_INCOMPATIBLES));
    }
}