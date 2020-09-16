<?php

namespace Helpers;

/**
 * Notice helper class
 */
class NoticeHelper
{
    const TYPE_NOTICE_DEFAULT = 'notice-error';

    const NOTICE_INFO = 'notice-info';

    const TYPES_NOTICE = [
        'notice-error',
        'notice-warning',
        'notice-success',
        'notice-info'
    ];

    /**
     * notice-error – error message displayed with a red border
     * notice-warning – warning message displayed with a yellow border
     * notice-success – success message displayed with a green border
     * notice-info - – info message displayed with a blue border
     *
     * @param text $message
     * @param string $type
     * @return void
     */
    public static function addNotice($message, $type)
    {
        $type = (in_array($type, self::TYPES_NOTICE))
            ? $type
            : self::TYPE_NOTICE_DEFAULT;

        add_action('admin_notices', function () use ($message, $type) {
            echo sprintf('<div class="notice %s is-dismissible"> 
                <p><strong>Atenção usuário do Melhor Envio</strong></p>
                <p>%s</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text"><a href="">Dismiss this notice.<a></span>
                </button>
            </div>', $type, $message);
        });
    }
}
