// app/Helpers/global_functions.php
<?php

use App\Helpers\GlobalHelper;

if (!function_exists('only_numbers')) {
    function only_numbers($date, $includeTime = false)
    {
        return GlobalHelper::onlyNumbers($date, $includeTime);
    }
}
