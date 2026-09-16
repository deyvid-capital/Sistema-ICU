<?php

namespace Tests\Unit\Filament;

use App\Filament\Imports\CsvEncodingDetector;
use PHPUnit\Framework\TestCase;

class CsvEncodingDetectorTest extends TestCase
{
    public function test_windows_1252_spanish_letters_are_detected_as_windows_1252_not_sjis(): void
    {
        $nucleosUtf8 = 'i7 8850H (2.60Ghz - 6 Núcleos 12 Hilos)';
        $nucleosWindows1252 = mb_convert_encoding($nucleosUtf8, 'Windows-1252', 'UTF-8');

        $this->assertFalse(mb_check_encoding($nucleosWindows1252, 'UTF-8'));
        $this->assertTrue(mb_check_encoding($nucleosWindows1252, 'SJIS-win'));
        $this->assertSame('Windows-1252', CsvEncodingDetector::detect($nucleosWindows1252));
        $this->assertSame($nucleosUtf8, mb_convert_encoding(
            $nucleosWindows1252,
            'UTF-8',
            CsvEncodingDetector::detect($nucleosWindows1252),
        ));
        $this->assertStringContainsString('昱', mb_convert_encoding($nucleosWindows1252, 'UTF-8', 'SJIS-win'));

        $utf8 = 'España,niño,año,rápido,código,pingüino,¿Qué?,¡Hola!,ÁREA TÉCNICA';
        $windows1252 = mb_convert_encoding($utf8, 'Windows-1252', 'UTF-8');

        $this->assertSame('Windows-1252', CsvEncodingDetector::detect($windows1252));
        $this->assertSame($utf8, mb_convert_encoding($windows1252, 'UTF-8', CsvEncodingDetector::detect($windows1252)));
    }

    public function test_utf8_spanish_letters_stay_utf8(): void
    {
        $utf8 = 'Núcleos,España,niño,rápido,pingüino,¿Qué?,ÁREA';

        $this->assertSame('UTF-8', CsvEncodingDetector::detect($utf8));
        $this->assertSame($utf8, mb_convert_encoding($utf8, 'UTF-8', CsvEncodingDetector::detect($utf8)));
    }

    public function test_utf8_bom_is_detected_as_utf8(): void
    {
        $utf8 = "\xEF\xBB\xBF".'año,Núcleos';

        $this->assertSame('UTF-8', CsvEncodingDetector::detect($utf8));
    }

    public function test_ascii_csv_is_detected_as_utf8(): void
    {
        $this->assertSame('UTF-8', CsvEncodingDetector::detect("service_tag,modelo\nABC123,Latitude"));
    }

    public function test_from_stream_rewinds_and_detects_windows_1252(): void
    {
        $utf8 = "nombre_micro\ni7 8850H (2.60Ghz - 6 Núcleos 12 Hilos)\n";
        $windows1252 = mb_convert_encoding($utf8, 'Windows-1252', 'UTF-8');

        $stream = fopen('php://memory', 'r+');
        $this->assertIsResource($stream);
        fwrite($stream, $windows1252);

        $encoding = CsvEncodingDetector::fromStream($stream);

        $this->assertSame('Windows-1252', $encoding);
        $this->assertSame(0, ftell($stream));

        fclose($stream);
    }
}
