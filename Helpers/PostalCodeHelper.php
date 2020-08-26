<?php

namespace Helpers;

class PostalCodeHelper
{
    /**
     * Function to format postal code
     *
     * @param string $postalCode
     * @return float
     */
    public static function postalcode($postalCode)
    {
        $postalCode = ExtractNumberHelper::extractOnlyNumber($postalCode);

        return floatval(str_pad($postalCode, 8, '0', STR_PAD_LEFT));
    }

}
