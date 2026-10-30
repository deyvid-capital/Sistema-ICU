<?php

namespace App\Services\Firma;

use App\Enums\EstadoFirmaOrigen;
use App\Exceptions\FirmaDigitalException;
use App\Models\PdfCelular;
use App\Models\PdfEquipo;
use App\Models\PdfMovimiento;
use Illuminate\Support\Facades\Storage;

/**
 * Una sola regla para cualquier camino que reemplace el archivo de un PDF de origen
 * (regenerar, subir un escaneo, editar el archivo): en firma está bloqueado; si ya
 * está firmado, hay que confirmar que se pierden las firmas.
 * Ver Docs/firma-digital/09-documentos-firmados-en-origen.md.
 */
class ReemplazoDocumentoFirmado
{
    public function __construct(private DocumentosEnFirma $enFirma) {}

    /**
     * Motivo por el que no se puede reemplazar, o null si se puede.
     */
    public function impedimento(PdfEquipo|PdfCelular|PdfMovimiento|null $pdf, bool $confirmado): ?string
    {
        if ($pdf === null) {
            return null;
        }

        if (($envio = $this->enFirma->envioEnCurso($pdf)) !== null) {
            return $this->enFirma->mensaje($envio);
        }

        if ($pdf->estaFirmado() && ! $confirmado) {
            return 'El documento «'.basename((string) $pdf->url).'» está '.mb_strtolower($pdf->estado_firma->getLabel()).'. Confirma que se perderán las firmas para reemplazarlo.';
        }

        return null;
    }

    /**
     * Sube un escaneo firmado a mano en la misma ruta del PDF y lo marca así.
     *
     * @throws FirmaDigitalException
     */
    public function subirFirmadoManual(PdfEquipo|PdfCelular|PdfMovimiento $pdf, string $contenido, bool $confirmado): void
    {
        if (($motivo = $this->impedimento($pdf, $confirmado)) !== null) {
            throw new FirmaDigitalException($motivo);
        }

        if (! Storage::disk($pdf::documentDisk())->put((string) $pdf->url, $contenido, ['visibility' => 'private'])) {
            throw new FirmaDigitalException('No se pudo guardar el archivo firmado.');
        }

        $pdf->marcarFirmadoManual(hash('sha256', $contenido));
    }

    /**
     * Después de reemplazar el archivo por uno sin firmas.
     */
    public function despuesDeRegenerar(PdfEquipo|PdfCelular|PdfMovimiento $pdf): void
    {
        if ($pdf->estado_firma !== EstadoFirmaOrigen::SinFirma) {
            $pdf->marcarSinFirma();
        }
    }
}
