<?php

namespace Helpers;

class SanitizeHelper
{
    public static function apply($data)
    {
        $sanitizeData = [];

        if (!is_array($data)) {
            return sanitize_text_field($data);
        }
        
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                foreach ($item as $key2 => $field) {
                    $sanitizeData[$key][$key2] = sanitize_text_field($field);
                }
                continue;
            }
            $sanitizeData[$key] = sanitize_text_field($item);
        }

        return $sanitizeData;
    }
}
