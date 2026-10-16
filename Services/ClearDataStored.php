<?php

namespace MelhorEnvio\Services;

/**
 * Compatibility shim for upgrades from 2.x.
 *
 * The 2.x Composer autoloader maps MelhorEnvio\Services\ to Services/ (no legacy/ prefix).
 * During upgrade the old autoloader stays in memory while new files replace the disk,
 * so this standalone class satisfies the old autoloader without pulling in legacy dependencies.
 *
 * Remove this file in 4.0 when legacy/ is dropped.
 */
class ClearDataStored {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    public function clear() {
        delete_option('wp_melhor_envio_seller');
        delete_option('shipping_services_melhor_envio');

        $meKey = 'melhor_envio_session';
        if (empty($_SESSION[$meKey])) {
            return;
        }

        foreach ($_SESSION[$meKey] as $hash => $item) {
            if ($hash !== 'notices_melhor_envio' && isset($_SESSION[$meKey][$hash])) {
                unset($_SESSION[$meKey][$hash]);
            }
        }
    }
}