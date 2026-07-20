<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PdfPreviewTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function previewRoutesProvider(): array
    {
        return [
            'entrega' => ['/pdf/preview/entrega'],
            'recepcion' => ['/pdf/preview/recepcion'],
            'nota-salida' => ['/pdf/preview/nota-salida'],
            'recepcion-externa' => ['/pdf/preview/recepcion-externa'],
        ];
    }

    #[DataProvider('previewRoutesProvider')]
    public function test_pdf_preview_returns_inline_pdf(string $uri): void
    {
        $response = $this->get($uri);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
