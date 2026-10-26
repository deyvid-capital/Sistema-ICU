<?php

namespace App\Services\Pdf;

use App\Enums\MovimientoFormularioPdfTipo;
use App\Exceptions\MovimientoFormularioPdfException;
use App\Models\Movimiento;
use App\Models\PdfMovimiento;
use App\Services\Firma\DocumentosEnFirma;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateMovimientoFormularioPdf
{
    public function __construct(
        private MovimientoFormularioPdfValidator $validator,
        private MovimientoFormularioPdfData $dataMapper,
        private BrowsershotPdfFactory $pdfFactory,
    ) {}

    /**
     * @throws MovimientoFormularioPdfException
     * @throws Throwable
     */
    public function execute(Model $record, MovimientoFormularioPdfTipo $tipo = MovimientoFormularioPdfTipo::AltaBaja): PdfMovimiento
    {
        $enFirma = app(DocumentosEnFirma::class);

        if (($envio = $enFirma->envioEnCurso($record)) !== null) {
            throw new MovimientoFormularioPdfException($enFirma->mensaje($envio));
        }

        $this->validator->validate($record, $tipo);

        /** @var Movimiento $movimiento */
        $movimiento = $record;

        $viewData = $this->dataMapper->for($movimiento, $tipo);
        $binary = $this->pdfFactory->fromView($tipo->view(), $viewData);

        $path = $tipo->pdfRelativePath($movimiento);
        $fecha = $movimiento->fecha;

        return DB::transaction(function () use ($movimiento, $tipo, $binary, $path, $fecha): PdfMovimiento {
            $disk = Storage::disk(PdfMovimiento::documentDisk());

            /** @var PdfMovimiento|null $existing */
            $existing = $movimiento->pdfs()
                ->where('nombre', $tipo->nombreOficial())
                ->latest('id')
                ->first();

            if ($existing !== null && filled($existing->url) && $existing->url !== $path) {
                $disk->delete($existing->url);
            }

            if (! $disk->put($path, $binary)) {
                throw new MovimientoFormularioPdfException('No se pudo guardar el archivo PDF en el disco.');
            }

            if ($existing !== null) {
                $existing->update([
                    'tipo' => $movimiento->tipo,
                    'fecha' => $fecha,
                    'url' => $path,
                ]);

                return $existing->refresh();
            }

            return $movimiento->pdfs()->create([
                'tipo' => $movimiento->tipo,
                'nombre' => $tipo->nombreOficial(),
                'fecha' => $fecha,
                'url' => $path,
            ]);
        });
    }
}
