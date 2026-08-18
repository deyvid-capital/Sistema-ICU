<?php

namespace App\Models;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\HasDocumentoOficialPdf;
use Database\Factories\RecepcionEquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecepcionEquipo extends Model
{
    /** @use HasFactory<RecepcionEquipoFactory> */
    use GuardsAgainstTrashedRelations;

    use HasDocumentoOficialPdf;
    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['asignacion'];

    protected $table = 'recepciones_equipos';

    protected $fillable = [
        'asignacion_id',
        'numero',
        'fecha',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
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
        return EquipoFormularioPdfTipo::Recepcion;
    }
}
