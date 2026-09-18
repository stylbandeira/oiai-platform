// app/Helpers/global_functions.php
<?php

use App\Helpers\GlobalHelper;

if (! function_exists('only_numbers')) {
    function only_numbers(string $date): string
    {
        return GlobalHelper::onlyNumbers($date);
    }
}
