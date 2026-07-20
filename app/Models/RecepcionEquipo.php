<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecepcionEquipo extends Model
{
    use GuardsAgainstTrashedRelations;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['asignacion'];

    protected $table = 'recepcion_equipos';

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
}
