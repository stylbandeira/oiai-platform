<?php

namespace App\Helpers;

class GlobalHelper
{
    /**
     * Return numbers from any string
     *
     * @param string $string
     */
    public static function onlyNumbers(string $string): string
    {
        return preg_replace('/[^0-9]/', '', $string) ?? '';
    }
}
