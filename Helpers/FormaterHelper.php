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
    public static function formatPhone($phone)
    {
        return str_replace(['(', ')', '-', ' '], [''], $phone);
    }

    /**
     * Remove characters and use only numbers.
     *
     * @param string $document
     * @return string $document
     */
    public static function formatDocument($document)
    {
        return str_replace(['-', '.', '/', ' '], [''], $document);
    }
}
