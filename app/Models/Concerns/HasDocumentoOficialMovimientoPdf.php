<?php

namespace App\Models\Concerns;

use App\Enums\MovimientoFormularioPdfTipo;
use App\Models\PdfMovimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasDocumentoOficialMovimientoPdf
{
    abstract public function pdfs(): HasMany;

    protected static function bootHasDocumentoOficialMovimientoPdf(): void
    {
        static::forceDeleting(function (Model $model): void {
            $model->pdfs()
                ->get()
                ->each
                ->delete();
        });
    }

    public function formularioPdfTipo(): MovimientoFormularioPdfTipo
    {
        return MovimientoFormularioPdfTipo::AltaBaja;
    }

    public function documentoOficial(): ?PdfMovimiento
    {
        return $this->pdfs()
            ->where('nombre', $this->formularioPdfTipo()->nombreOficial())
            ->latest('id')
            ->first();
    }

    public function hasDocumentoOficial(): bool
    {
        $documento = $this->documentoOficial();

        return $documento !== null && $documento->hasStoredDocument();
    }
}
