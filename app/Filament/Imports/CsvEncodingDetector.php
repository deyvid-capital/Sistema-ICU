<?php

namespace App\Filament\Imports;

class CsvEncodingDetector
{
    /**
     * Excel en Windows suele guardar CSV en Windows-1252 (á, é, í, ó, ú, ü, ñ).
     * Filament prueba SJIS/GB18030 antes y convierte mal: Núcleos → N昱leos.
     */
    public static function detect(string $contentSample): string
    {
        if (str_starts_with($contentSample, "\xEF\xBB\xBF")) {
            return 'UTF-8';
        }

        if (mb_check_encoding($contentSample, 'UTF-8')) {
            return 'UTF-8';
        }

        return 'Windows-1252';
    }

    /**
     * @param  resource  $resource
     */
    public static function fromStream(mixed $resource): string
    {
        rewind($resource);

        $lineCount = 0;
        $contentSample = '';

        while ((! feof($resource)) && ($lineCount < 20)) {
            $line = fgets($resource);

            if ($line === false) {
                break;
            }

            $contentSample .= $line;
            $lineCount++;
        }

        rewind($resource);

        return self::detect($contentSample);
    }
}
