<?php

namespace Services;

/**
 * Service responsible for managing the data stored in the session
 */
class SessionNoticeService
{
    const ID_NOTICES_SESSION = 'notices_melhor_envio';

    public function __construct()
    {
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * function to save notice in session
     *
     * @param string $notice
     * @return void
     */
    public function add($notice)
    {
        $notices = $_SESSION[self::ID_NOTICES_SESSION];

        $key = array_search($notice, array_column($notices, 'notice'));
        if (!$key) {
            $html = sprintf(
                '<p>%s <a href="%s"></br>
                <small>remover aviso</small></a></p>',
                $notice,
                get_admin_url() . 'admin-ajax.php?action=remove_notices&id=' . md5($notice)
            );

            $_SESSION[self::ID_NOTICES_SESSION][md5($notice)] = [
                'notice' => $html,
                'created' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * function to remove notice in session by key.
     *
     * @param int $index
     * @return void
     */
    public function remove($index)
    {
        $notices = $_SESSION[self::ID_NOTICES_SESSION];
        unset($notices[$index]);
        unset($_SESSION[self::ID_NOTICES_SESSION]);
        $_SESSION[self::ID_NOTICES_SESSION] = $notices;

        wp_redirect($_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * function to list all notices in session.
     *
     * @return array
     */
    public function get()
    {
        $notices = $_SESSION[self::ID_NOTICES_SESSION];

        foreach ($notices as $key => $notice) {
            $dateLimit = date('Y-m-d H:i:s', strtotime('-5 minutes'));
            if ($dateLimit > $notice['created']) {
                //unset($notices[$key]);
            }
        }

        $_SESSION[self::ID_NOTICES_SESSION] = $notices;
        return $notices;
    }
}
