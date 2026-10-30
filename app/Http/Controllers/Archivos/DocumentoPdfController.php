<?php

namespace App\Http\Controllers\Archivos;

use App\Http\Controllers\Controller;
use App\Models\PdfCelular;
use App\Models\PdfEquipo;
use App\Models\PdfMovimiento;
use App\Models\ReciboEquipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sirve los PDF confidenciales (disco privado o s3) solo con sesión y permiso: el
 * de ver el PDF o el del registro al que pertenece (entrega, movimiento, equipo...).
 */
class DocumentoPdfController extends Controller
{
    /**
     * @var array<string, class-string<Model>>
     */
    public const TIPOS = [
        'pdf_equipo' => PdfEquipo::class,
        'pdf_celular' => PdfCelular::class,
        'pdf_movimiento' => PdfMovimiento::class,
        'recibo_equipo' => ReciboEquipo::class,
    ];

    public function ver(string $tipo, int $id): StreamedResponse
    {
        return $this->responder($tipo, $id, 'inline');
    }

    public function descargar(string $tipo, int $id): StreamedResponse
    {
        return $this->responder($tipo, $id, 'attachment');
    }

    private function responder(string $tipo, int $id, string $disposicion): StreamedResponse
    {
        /** @var PdfEquipo|PdfCelular|PdfMovimiento|ReciboEquipo $documento */
        $documento = self::TIPOS[$tipo]::query()->findOrFail($id);

        abort_unless($this->puedeVer($documento), 403);

        $disco = Storage::disk($documento::documentDisk());
        abort_unless(filled($documento->url) && $disco->exists($documento->url), 404);

        return $disco->response($documento->url, basename($documento->url), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ], $disposicion);
    }

    private function puedeVer(Model $documento): bool
    {
        if (Gate::allows('view', $documento)) {
            return true;
        }

        $registro = match (true) {
            $documento instanceof PdfMovimiento => $documento->movimiento,
            $documento instanceof ReciboEquipo => $documento->equipo,
            default => $documento->documento,
        };

        return $registro !== null && Gate::allows('view', $registro);
    }
}
