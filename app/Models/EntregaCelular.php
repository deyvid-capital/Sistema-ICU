<?php

namespace App\Models;

use App\Enums\CelularFormularioPdfTipo;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\HasDocumentoOficialCelularPdf;
use Database\Factories\EntregaCelularFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntregaCelular extends Model
{
    /** @use HasFactory<EntregaCelularFactory> */
    use GuardsAgainstTrashedRelations;

    use HasDocumentoOficialCelularPdf;
    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['asignacion'];

    protected $table = 'entregas_celulares';

    protected $fillable = [
        'asignacion_cel_id',
        'numero',
        'fecha',
        'nota',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Get the phone assignment that owns the delivery.
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsigCelular::class, 'asignacion_cel_id')->withTrashed();
    }

    /**
     * Get all of the delivery's PDF files.
     */
    public function pdfs(): MorphMany
    {
        return $this->morphMany(PdfCelular::class, 'documento');
    }

    /**
     * Get all of the delivery's signers.
     */
    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }

    public static function formularioPdfTipo(): CelularFormularioPdfTipo
    {
        return CelularFormularioPdfTipo::Entrega;
    }
}
