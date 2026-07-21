<?php

namespace App\Models;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\HasDocumentoOficialPdf;
use Database\Factories\EntregaEquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntregaEquipo extends Model
{
    /** @use HasFactory<EntregaEquipoFactory> */
    use GuardsAgainstTrashedRelations;

    use HasDocumentoOficialPdf;
    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['asignacion'];

    protected $table = 'entrega_equipos';

    protected $fillable = [
        'asignacion_id',
        'numero',
        'fecha',
        'mouse',
        'cooler',
        'audifonos',
        'observaciones',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'mouse' => 'boolean',
            'cooler' => 'boolean',
            'audifonos' => 'boolean',
        ];
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionEquipo::class, 'asignacion_id')->withTrashed();
    }

    public function pdfs(): MorphMany
    {
        return $this->morphMany(PdfEquipo::class, 'documento');
    }

    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }

    public static function formularioPdfTipo(): EquipoFormularioPdfTipo
    {
        return EquipoFormularioPdfTipo::Entrega;
    }
}
