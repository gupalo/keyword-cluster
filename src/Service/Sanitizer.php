<?php

namespace App\Service;

class Sanitizer
{
    public static function safeListItem(?string $url): string
    {
        return str_replace(['"', "\r", "\n", "\t", ', '], ['\'', ' ', ' ', ' ', '  '], $url);
    }
}
