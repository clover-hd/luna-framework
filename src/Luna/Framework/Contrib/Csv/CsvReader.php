<?php

namespace Luna\Framework\Contrib\Csv;

class CsvReader
{
    public static function readCsv(string $filepath, string $encoding, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        $fp = tmpfile();
        $data = file_get_contents($filepath);
        fwrite($fp, mb_convert_encoding($data, 'UTF-8', $encoding));
        fseek($fp, 0);

        while ($line = fgetcsv($fp, 0, $delimiter, $enclosure, $escape)) {
            $csvData[] = $line;
        }

        fclose($fp);

        return new CsvData($csvData);
    }
}