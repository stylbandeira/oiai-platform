<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

class GlobalHelper
{
    /**
     * Return numbers from any string
     *
     * @param [type] $string
     * @return string
     */
    public static function onlyNumbers($string): string
    {
        return $cpfLimpo = preg_replace('/[^0-9]/', '', $string);
    }
}
