<?php

namespace App\Service;

class TsvFileWriter
{
    public static function write(string $filename, array $items): void
    {
        $rows = [];
        foreach ($items as $item) {
            if (!$rows) {
                $rows[] = implode("\t", array_keys($item));
            }
            $rows[] = implode("\t", $item);
        }

        file_put_contents($filename, implode("\n", $rows));
    }
}