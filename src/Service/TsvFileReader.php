<?php

namespace App\Service;

class TsvFileReader
{
    public static function read(string $keywordsFilename, array $columnNames): array
    {
        $result = [];
        $f = fopen($keywordsFilename, 'rb');
        while (!feof($f)) {
            $s = trim(fgets($f));
            if (!$s) {
                continue;
            }
            $cols = explode("\t", $s);
            $item = [];
            for ($i = 0, $iMax = count($columnNames); $i < $iMax; $i++) {
                $item[$columnNames[$i]] = $cols[$i] ?? '';
            }

            $result[] = $item;
        }
        fclose($f);

        return $result;
    }
}