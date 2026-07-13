<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Firmante extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $fillable = [
        'usuario_id',
        'fecha',
        'documento_id',
        'documento_type',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Get the parent documentable model.
     */
    public function documento(): MorphTo
    {
        return $this->morphTo();
    }
}
