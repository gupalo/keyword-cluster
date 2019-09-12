<?php

namespace App\Log;

class Logger
{
    public static function log($s): void
    {
        if (is_array($s)) {
            $s = print_r($s, true);
        }

        printf('[%s] %s' . "\n", date('Y-m-d H:i:s'), $s);
    }
}
