<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Database\Factories\AsigCargadorCelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AsigCargadorCel extends Model
{
    /** @use HasFactory<AsigCargadorCelFactory> */
    use GuardsAgainstTrashedRelations;

    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['celular', 'cargador'];

    protected $table = 'asignaciones_cargadores_celulares';

    protected $fillable = [
        'celulares_id',
        'cargador_serial',
        'motivo',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function celular(): BelongsTo
    {
        return $this->belongsTo(Celular::class, 'celulares_id', 'id')->withTrashed();
    }

    public function cargador(): BelongsTo
    {
        return $this->belongsTo(CargadorCel::class, 'cargador_serial', 'serial')->withTrashed();
    }
}
