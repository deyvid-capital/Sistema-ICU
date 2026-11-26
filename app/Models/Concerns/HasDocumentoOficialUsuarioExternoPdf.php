<?php

namespace App\Models\Concerns;

use App\Enums\UsuarioExternoFormularioPdfTipo;
use App\Models\PdfUsuarioExterno;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasDocumentoOficialUsuarioExternoPdf
{
    abstract public function pdfs(): HasMany;

    protected static function bootHasDocumentoOficialUsuarioExternoPdf(): void
    {
        static::forceDeleting(function (Model $model): void {
            $model->pdfs()
                ->get()
                ->each
                ->delete();
        });
    }

    public function formularioPdfTipo(): UsuarioExternoFormularioPdfTipo
    {
        return UsuarioExternoFormularioPdfTipo::Formulario;
    }

    public function documentoOficial(): ?PdfUsuarioExterno
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
