<?php

namespace App\Parser;

class UrlParser
{
    public static function extractDomain($url): string
    {
        $urlWithoutSchema = preg_replace('#^[a-zA-Z\d]+://#', '', $url);

        return preg_replace('#/.*$#', '', $urlWithoutSchema);
    }
}