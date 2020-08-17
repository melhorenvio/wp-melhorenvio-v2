<?php

namespace Services;

/**
 * Service responsible for managing the data stored in the session
 */
class SessionService
{
    /**
     * Minutes that the data must be stored in the session
     */
    const TIME_SESSION = 1;

    public function __construct()
    {
        if (empty(session_id())) {
            session_start();
        }
    }

    /**
     * Function to get data stored on session.
     *
     * @param string $key
     * @return object
     */
    public function getDataCached($key)
    {
        if ($this->isExpiredCache($key)) {
            return false;
        }

        return $_SESSION[$key]['data'];
    }

    /**
     * Function to save data user on session.   
     *
     * @param string $key
     * @param mixed $data
     * @return void
     */
    public function storeData($key, $data)
    {
        $_SESSION[$key]['data'] = $data;
        $_SESSION[$key]['created'] = date('Y-m-d H:i:s');
    }   

    /**
     * Function to check if data cacked is expired
     * 
     * @param string $key
     * @return boolean
     */
    public function isExpiredCache($key)
    {
        if (empty($_SESSION[$key]['created']) ) {
            return true;
        }

        $created = $_SESSION[$key]['created'];

        $dateLimit = date(
            'Y-m-d H:i:s', 
            strtotime(sprintf("-%d minutes", self::TIME_SESSION))
        );

        if ($dateLimit > $created) {
            unset($_SESSION[$key]);
            return true;
        }

        if (empty($_SESSION[$key]['data'])) {
            return true;
        }

        return false;
    }
}
