<?php

namespace App\Http\Controllers;

use App\Services\Pdf\BrowsershotPdfFactory;
use Illuminate\Http\Response;

class PdfPreviewController extends Controller
{
    public function __construct(private BrowsershotPdfFactory $pdfFactory) {}

    public function entrega(): Response
    {
        return $this->preview('PlantillasPdf.FormEntrega', 'formulario-entrega.pdf');
    }

    public function recepcion(): Response
    {
        return $this->preview('PlantillasPdf.FormRecepcion', 'formulario-recepcion.pdf');
    }

    public function notaSalida(): Response
    {
        return $this->preview('PlantillasPdf.FormNotaSalida', 'nota-salida.pdf');
    }

    public function recepcionExterna(): Response
    {
        return $this->preview('PlantillasPdf.FormRecepcionExterna', 'recepcion-externa.pdf');
    }

    /**
     * @param  view-string  $view
     */
    private function preview(string $view, string $filename): Response
    {
        $pdf = $this->pdfFactory->fromView($view);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
