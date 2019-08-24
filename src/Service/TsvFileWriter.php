<?php

namespace App\Service;

use RuntimeException;

class TsvFileWriter
{
    public static function write(string $filename, array $items): void
    {
        $dirname = dirname($filename);
        if (!is_dir($dirname) && !mkdir($dirname, 0777, true) && !is_dir($dirname)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dirname));
        }

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
