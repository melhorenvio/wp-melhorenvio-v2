<?php

namespace Helpers;

class ExtractNumberHelper
{
    /**
     * Function to extract numbers
     *
     * @param string $value
     * @return float
     */
    public static function extract($value)
    {
        return floatval(preg_replace('/\D/', '', $value));
    }

}
