<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movimiento extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $fillable = [
        'usuario_id',
        'tipo',
        'fecha',
        'cargo_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id', 'id_cargo');
    }
}
