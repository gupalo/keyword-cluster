<?php

namespace App\Service;

use RuntimeException;

class TsvFileReader
{
    /**
     * @param string $filename
     * @param array $columnNames
     * @return array
     * @throws RuntimeException
     */
    public static function read(string $filename, array $columnNames): array
    {
        if (!is_file($filename)) {
            throw new RuntimeException(sprintf('File "%s" is not found', $filename));
        }

        $result = [];
        $f = fopen($filename, 'rb');
        while (!feof($f)) {
            $s = trim(fgets($f));
            if (!$s) {
                continue;
            }
            $cols = explode("\t", $s);
            $item = [];
            for ($i = 0, $iMax = count($columnNames); $i < $iMax; $i++) {
                $item[$columnNames[$i]] = trim($cols[$i]) ?? '';
            }

            $result[] = $item;
        }
        fclose($f);

        return $result;
    }
}
