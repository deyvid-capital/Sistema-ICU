<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareUsuario extends Model
{
    use BelongsToUsuario, GuardsAgainstTrashedRelations, HasFactory, SoftDeletes, TogglesEstadoOnSoftDelete;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $table = 'software_usuarios';

    protected $fillable = [
        'usuario_id',
        'id_software',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    /**
     * Get the software for the assignment.
     */
    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'id_software', 'id_software');
    }
}
