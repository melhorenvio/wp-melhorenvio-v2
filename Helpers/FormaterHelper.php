<?php

namespace Helpers;

class FormaterHelper
{
    /**
     * Remove characters and use only numbers.
     *
     * @param string $phone
     * @return string $phone
     */
    public function formatPhone($phone)
    {
        return str_replace(['(', ')', '-', ' '], [''], $phone);
    }

    /**
     * Remove characters and use only numbers.
     *
     * @param string $document
     * @return string $document
     */
    public function formatDocuemnt($document)
    {
        return str_replace(['-', '.', '/', ' '], [''], $document);
    }
}
