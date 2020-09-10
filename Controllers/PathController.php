<?php

namespace Controllers;

class PathController
{
    /**
     * Function to return the patch from the wordpress plugins folder, 
     * returning the default and customized path by the administrator.
     *
     * @return json
     */
    public function getPathPlugin()
    {
        return wp_send_json([
            'custom' => get_option('melhor_envio_path_plugins', false),
            'native' => ABSPATH . 'wp-content/plugins'
        ]);
    }
}
