<?php

namespace App\Http\Controllers;

use App\Models\Movimiento;
use App\Services\Pdf\BrowsershotPdfFactory;
use App\Services\Pdf\MovimientoFormularioPdfData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class MovimientoFormularioPdfController extends Controller
{
    public function __construct(
        private MovimientoFormularioPdfData $dataMapper,
        private BrowsershotPdfFactory $pdfFactory,
    ) {}

    public function previewHtml(Movimiento $movimiento): View
    {
        $viewData = $this->dataMapper->for($movimiento);

        return view('PlantillasPdf.FormAltaBaja', $viewData);
    }

    public function previewPdf(): Response
    {
        $pdf = $this->pdfFactory->fromView('PlantillasPdf.FormAltaBaja');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="formulario-alta-baja.pdf"',
        ]);
    }
}
