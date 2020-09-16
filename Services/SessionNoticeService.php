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
        if (!array_search($notice, array_column($notices, 'notice'))) {
            $_SESSION[self::ID_NOTICES_SESSION][] = [
                'notice' => $notice,
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
            $dateLimit = date('Y-m-d H:i:s', strtotime('-2 minutes'));
            if ($dateLimit > $notice['created']) {
                unset($notices[$key]);
            }
        }

        $_SESSION[self::ID_NOTICES_SESSION] = $notices;
        return $notices;
    }
}
