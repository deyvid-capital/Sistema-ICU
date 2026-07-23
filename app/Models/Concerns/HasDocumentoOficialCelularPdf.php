<?php

namespace App\Models\Concerns;

use App\Enums\CelularFormularioPdfTipo;
use App\Models\PdfCelular;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDocumentoOficialCelularPdf
{
    abstract public static function formularioPdfTipo(): CelularFormularioPdfTipo;

    abstract public function pdfs(): MorphMany;

    protected static function bootHasDocumentoOficialCelularPdf(): void
    {
        static::forceDeleting(function (Model $model): void {
            $model->pdfs()
                ->get()
                ->each
                ->delete();
        });
    }

    public function documentoOficial(): ?PdfCelular
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
