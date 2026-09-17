<?php

namespace Database\Seeders\Concerns;

trait ReadsCsvData
{
    /**
     * @return list<array<string, string>>
     */
    protected function rowsFromCsv(string $filename): array
    {
        $path = database_path('data/'.$filename);
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException("No se pudo leer {$path}.");
        }

        try {
            $header = fgetcsv($handle, separator: ';', escape: '\\');

            if ($header === false) {
                return [];
            }

            $header = array_map($this->cleanCsvValue(...), $header);
            $rows = [];

            while (($row = fgetcsv($handle, separator: ';', escape: '\\')) !== false) {
                if ($row === [null]) {
                    continue;
                }

                $values = array_pad(array_map($this->cleanCsvValue(...), $row), count($header), '');
                $rows[] = array_combine($header, $values);
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    protected function cleanCsvValue(?string $value): string
    {
        $value = (string) $value;
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;

        if (! mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        $value = str_replace("\u{00A0}", ' ', $value);

        return trim($value);
    }

    protected function nullableCsvValue(?string $value): ?string
    {
        $value = $this->cleanCsvValue($value);

        if ($value === '' || $value === '-') {
            return null;
        }

        return $value;
    }
}
