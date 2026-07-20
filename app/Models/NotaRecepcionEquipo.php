<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaRecepcionEquipo extends Model
{
    use GuardsAgainstTrashedRelations;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['equipo', 'usuarioExterno'];

    protected $table = 'nota_recepcion_equipos';

    protected $fillable = [
        'equipo_id',
        'usuarios_externo_id',
        'numero',
        'fecha',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id', 'service_tag')->withTrashed();
    }

    public function usuarioExterno(): BelongsTo
    {
        return $this->belongsTo(UsuarioExterno::class, 'usuarios_externo_id')->withTrashed();
    }

    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }
}
