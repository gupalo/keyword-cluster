<?php

namespace App\Parser;

class KeywordParser
{
    private $volumes = [];

    public function __construct(array $rows)
    {
        foreach ($rows as $row) {
            $this->volumes[$row['keyword']] = (int)str_replace([',', ' '], '', trim($row['volume']));
        }
    }

    public function getVolume(string $keyword): ?int
    {
        return $this->volumes[$keyword] ?? null;
    }
}