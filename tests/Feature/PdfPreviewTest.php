<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PdfPreviewTest extends TestCase
{
    use RefreshDatabase;

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
        $response = $this->actingAs(User::factory()->create())->get($uri);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    #[DataProvider('previewRoutesProvider')]
    public function test_pdf_preview_requires_a_session(string $uri): void
    {
        $this->get($uri)->assertRedirect(route('filament.admin.auth.login'));
    }
}
