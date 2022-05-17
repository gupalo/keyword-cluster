<?php

namespace App\Service;

class Sanitizer
{
    public static function safeListItem(?string $url): string
    {
        if (!$url) {
            return '';
        }

        return str_replace(['"', "\r", "\n", "\t", ', '], ['\'', ' ', ' ', ' ', '  '], $url);
    }
}
