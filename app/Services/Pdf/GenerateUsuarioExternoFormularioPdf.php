<?php

namespace App\Services\Pdf;

use App\Enums\UsuarioExternoFormularioPdfTipo;
use App\Exceptions\UsuarioExternoFormularioPdfException;
use App\Models\PdfUsuarioExterno;
use App\Models\UsuarioExterno;
use App\Services\Firma\DocumentosEnFirma;
use App\Services\Firma\ReemplazoDocumentoFirmado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Genera y guarda el PDF oficial del Formulario de Usuario Externo. Si ya existe
 * lo reemplaza (regenerar); un PDF firmado solo se reemplaza con confirmación y
 * no se toca mientras está en un envío a firma.
 */
class GenerateUsuarioExternoFormularioPdf
{
    public function __construct(
        private UsuarioExternoFormularioPdfValidator $validator,
        private UsuarioExternoFormularioPdfData $dataMapper,
        private BrowsershotPdfFactory $pdfFactory,
    ) {}

    /**
     * @throws UsuarioExternoFormularioPdfException
     * @throws Throwable
     */
    public function execute(UsuarioExterno $usuarioExterno, bool $reemplazarFirmado = false): PdfUsuarioExterno
    {
        $tipo = UsuarioExternoFormularioPdfTipo::Formulario;
        $enFirma = app(DocumentosEnFirma::class);

        if (($envio = $enFirma->envioEnCurso($usuarioExterno)) !== null) {
            throw new UsuarioExternoFormularioPdfException($enFirma->mensaje($envio));
        }

        if (($motivo = app(ReemplazoDocumentoFirmado::class)->impedimento($usuarioExterno->documentoOficial(), $reemplazarFirmado)) !== null) {
            throw new UsuarioExternoFormularioPdfException($motivo);
        }

        $this->validator->validate($usuarioExterno);

        $binary = $this->pdfFactory->fromView($tipo->view(), $this->dataMapper->for($usuarioExterno));
        $path = $tipo->pdfRelativePath($usuarioExterno);
        $fecha = now(config('app.display_timezone'))->toDateString();

        return DB::transaction(function () use ($usuarioExterno, $tipo, $binary, $path, $fecha): PdfUsuarioExterno {
            $disk = Storage::disk(PdfUsuarioExterno::documentDisk());
            $existing = $usuarioExterno->documentoOficial();

            if ($existing !== null && filled($existing->url) && $existing->url !== $path) {
                $disk->delete($existing->url);
            }

            if (! $disk->put($path, $binary)) {
                throw new UsuarioExternoFormularioPdfException('No se pudo guardar el archivo PDF en el disco.');
            }

            if ($existing !== null) {
                $existing->update([
                    'fecha' => $fecha,
                    'url' => $path,
                ]);

                app(ReemplazoDocumentoFirmado::class)->despuesDeRegenerar($existing);

                return $existing->refresh();
            }

            return $usuarioExterno->pdfs()->create([
                'nombre' => $tipo->nombreOficial(),
                'fecha' => $fecha,
                'url' => $path,
            ]);
        });
    }
}
