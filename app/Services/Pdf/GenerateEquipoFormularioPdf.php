<?php

namespace App\Services\Pdf;

use App\Enums\EquipoFormularioPdfTipo;
use App\Exceptions\EquipoFormularioPdfException;
use App\Models\PdfEquipo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class GenerateEquipoFormularioPdf
{
    public function __construct(
        private EquipoFormularioPdfValidator $validator,
        private EquipoFormularioPdfData $dataMapper,
        private BrowsershotPdfFactory $pdfFactory,
    ) {}

    /**
     * @throws EquipoFormularioPdfException
     * @throws Throwable
     */
    public function execute(Model $record, EquipoFormularioPdfTipo $tipo): PdfEquipo
    {
        $this->validator->validate($record, $tipo);

        $viewData = $this->dataMapper->for($record, $tipo);
        $binary = $this->pdfFactory->fromView($tipo->view(), $viewData);

        $numero = (string) $record->getAttribute('numero');
        $fecha = $record->getAttribute('fecha');
        $fechaSlug = filled($fecha) ? $fecha->format('Y-m-d') : now()->format('Y-m-d');
        $path = 'pdf-equipos/'.Str::slug($numero).'_'.$fechaSlug.'.pdf';

        return DB::transaction(function () use ($record, $tipo, $binary, $path, $fecha): PdfEquipo {
            $disk = Storage::disk(PdfEquipo::DOCUMENT_DISK);

            /** @var PdfEquipo|null $existing */
            $existing = $record->pdfs()
                ->where('nombre', $tipo->nombreOficial())
                ->latest('id')
                ->first();

            if ($existing !== null && filled($existing->url) && $existing->url !== $path) {
                $disk->delete($existing->url);
            }

            if (! $disk->put($path, $binary)) {
                throw new EquipoFormularioPdfException('No se pudo guardar el archivo PDF en el disco.');
            }

            if ($existing !== null) {
                $existing->update([
                    'fecha' => $fecha,
                    'url' => $path,
                ]);

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
