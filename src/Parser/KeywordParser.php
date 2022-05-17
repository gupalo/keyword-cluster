<?php

namespace App\Parser;

class KeywordParser
{
    private $volumes = [];

    public function load(array $rows): void
    {
        foreach ($rows as $row) {
            $volume = (int)str_replace([',', ' '], '', trim($row['volume']));
            $this->volumes[$row['keyword']] = $volume;
        }
    }

    public function getVolume(string $keyword): ?int
    {
        return $this->volumes[$keyword] ?? null;
    }
}
