<?php

namespace Helpers;

class PostalCodeHelper
{
    /**
     * Function to format postal code
     *
     * @param string $postalCode
     * @return string
     */
    public static function postalcode($postalCode)
    {
        $postalCode = ExtractNumberHelper::extractOnlyNumber($postalCode);

        return str_pad($postalCode, 8, '0', STR_PAD_LEFT);
    }

}
