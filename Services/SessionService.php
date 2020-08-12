<?php

namespace Services;

class SessionService
{
    public function clear()
    {
        $codeStore = md5(get_option('home'));

        if (isset($_SESSION[$codeStore]['cotations'])) {

            foreach ($_SESSION[$codeStore]['cotations'] as $key => $cotation) {

                if (!isset($cotation['created'])) {
                    unset($_SESSION[$codeStore]['cotations'][$key]);
                }

                if ($this->isExpiredQuotationCached($cotation)) {
                    unset($_SESSION[$codeStore]['cotations'][$key]);
                }
            }
        }
    }

    /**
     * Function to check if the quote has expired in the session
     *
     * @param array $quotation
     * @return boolean
     */
    public function isExpiredQuotationCached($quotation)
    {
        $dateNow = date("Y-m-d h:i:s");

        return (date('Y-m-d H:i:s', strtotime('+2 hours', strtotime($quotation['created']))) < $dateNow);
    }

    public function delete()
    {
        $codeStore = md5(get_option('home'));

        delete_option('melhorenvio_user_info');

        unset($_SESSION[$codeStore]);

        return $_SESSION;
    }
}
