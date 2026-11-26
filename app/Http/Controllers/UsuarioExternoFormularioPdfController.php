<?php

namespace App\Http\Controllers;

use App\Enums\UsuarioExternoFormularioPdfTipo;
use App\Models\UsuarioExterno;
use App\Services\Pdf\BrowsershotPdfFactory;
use App\Services\Pdf\UsuarioExternoFormularioPdfData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class UsuarioExternoFormularioPdfController extends Controller
{
    public function __construct(
        private UsuarioExternoFormularioPdfData $dataMapper,
        private BrowsershotPdfFactory $pdfFactory,
    ) {}

    /**
     * Vista previa con los datos reales, sin generar ni guardar el PDF.
     */
    public function previewHtml(UsuarioExterno $usuarioExterno): View
    {
        Gate::authorize('view', $usuarioExterno);

        return view(UsuarioExternoFormularioPdfTipo::Formulario->view(), $this->dataMapper->for($usuarioExterno));
    }

    /**
     * Plantilla con datos de ejemplo, para revisar el diseño.
     */
    public function previewPdf(): Response
    {
        $pdf = $this->pdfFactory->fromView(UsuarioExternoFormularioPdfTipo::Formulario->view());

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="formulario-usuario-externo.pdf"',
        ]);
    }
}
