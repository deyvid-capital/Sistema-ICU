<?php

namespace App\Services\Pdf;

use App\Enums\CelularFormularioPdfTipo;
use App\Exceptions\CelularFormularioPdfException;
use App\Models\PdfCelular;
use App\Services\Firma\DocumentosEnFirma;
use App\Services\Firma\ReemplazoDocumentoFirmado;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateCelularFormularioPdf
{
    public function __construct(
        private CelularFormularioPdfValidator $validator,
        private CelularFormularioPdfData $dataMapper,
        private BrowsershotPdfFactory $pdfFactory,
    ) {}

    /**
     * @throws CelularFormularioPdfException
     * @throws Throwable
     */
    public function execute(Model $record, CelularFormularioPdfTipo $tipo, bool $reemplazarFirmado = false): PdfCelular
    {
        $enFirma = app(DocumentosEnFirma::class);

        if (($envio = $enFirma->envioEnCurso($record)) !== null) {
            throw new CelularFormularioPdfException($enFirma->mensaje($envio));
        }

        $oficial = $record->pdfs()->where('nombre', $tipo->nombreOficial())->latest('id')->first();

        if (($motivo = app(ReemplazoDocumentoFirmado::class)->impedimento($oficial, $reemplazarFirmado)) !== null) {
            throw new CelularFormularioPdfException($motivo);
        }

        $this->validator->validate($record, $tipo);

        $viewData = $this->dataMapper->for($record, $tipo);
        $binary = $this->pdfFactory->fromView($tipo->view(), $viewData);

        $numero = (string) $record->getAttribute('numero');
        $fecha = $record->getAttribute('fecha');
        $path = $tipo->pdfRelativePath($numero);

        return DB::transaction(function () use ($record, $tipo, $binary, $path, $fecha): PdfCelular {
            $disk = Storage::disk(PdfCelular::documentDisk());

            /** @var PdfCelular|null $existing */
            $existing = $record->pdfs()
                ->where('nombre', $tipo->nombreOficial())
                ->latest('id')
                ->first();

            if ($existing !== null && filled($existing->url) && $existing->url !== $path) {
                $disk->delete($existing->url);
            }

            if (! $disk->put($path, $binary)) {
                throw new CelularFormularioPdfException('No se pudo guardar el archivo PDF en el disco.');
            }

            if ($existing !== null) {
                $existing->update([
                    'fecha' => $fecha,
                    'url' => $path,
                ]);

                app(ReemplazoDocumentoFirmado::class)->despuesDeRegenerar($existing);

                return $existing->refresh();
            }

            return $record->pdfs()->create([
                'nombre' => $tipo->nombreOficial(),
                'fecha' => $fecha,
                'url' => $path,
            ]);
        });
    }
}
