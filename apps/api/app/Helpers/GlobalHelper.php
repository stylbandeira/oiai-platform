<?php

namespace App\Helpers;

class GlobalHelper
{
    /**
     * Return numbers from any string
     *
     * @param [type] $string
     */
    public static function onlyNumbers($string): string
    {
        return $cpfLimpo = preg_replace('/[^0-9]/', '', $string);
    }
}
