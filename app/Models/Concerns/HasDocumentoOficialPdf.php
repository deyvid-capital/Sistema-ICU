<?php

namespace App\Models\Concerns;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\PdfEquipo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDocumentoOficialPdf
{
    abstract public static function formularioPdfTipo(): EquipoFormularioPdfTipo;

    abstract public function pdfs(): MorphMany;

    public function documentoOficial(): ?PdfEquipo
    {
        return $this->pdfs()
            ->where('nombre', static::formularioPdfTipo()->nombreOficial())
            ->latest('id')
            ->first();
    }

    public function hasDocumentoOficial(): bool
    {
        $documento = $this->documentoOficial();

        return $documento !== null && $documento->hasStoredDocument();
    }
}
