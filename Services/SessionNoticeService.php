<?php

namespace Services;

/**
 * Service responsible for managing the data stored in the session
 */
class SessionNoticeService
{
    const ID_NOTICES_SESSION = 'notices_melhor_envio';

    /**
     * function to save notice in session
     *
     * @param string $notice
     * @return void
     */
    public function add($notice)
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        $notices = (!empty($_SESSION[self::ID_NOTICES_SESSION]))
            ? $_SESSION[self::ID_NOTICES_SESSION]
            : [];

        if (!empty($notices)) {
            $key = array_search($notice, array_column($notices, 'notice'));
            if (!$key) {
                $this->insertSession($notice);
            }
            return;
        }

        $this->insertSession($notice);

        session_write_close();
    }

    /**
     * function to insert notice insession.
     *
     * @param string $notice
     * @return void
     */
    private function insertSession($notice)
    {
        $html = sprintf(
            '<p>%s <a href="%s"></br>
            <small>Não exibir mais</small></a></p>',
            $notice,
            get_admin_url() . 'admin-ajax.php?action=remove_notices&id=' . md5($notice)
        );

        $_SESSION[self::ID_NOTICES_SESSION][md5($notice)] = [
            'notice' => $html,
            'created' => date('Y-m-d H:i:s')
        ];
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
     * @return bool|array
     */
    public function get()
    {
        $notices = false;

        if (!isset($_SESSION)) {
            session_start();
        }

        if (!empty($_SESSION[self::ID_NOTICES_SESSION])) {
            $notices = $_SESSION[self::ID_NOTICES_SESSION];
        }

        session_write_close();

        return $notices;
    }
}
