<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MantenimientoEquipo extends Model
{
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['equipo'];

    protected $table = 'mantenimiento_equipo';

    protected $fillable = [
        'service_tag',
        'fecha_soporte',
        'cap_antes',
        'cap_despues',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_soporte' => 'date',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'service_tag', 'service_tag')->withTrashed();
    }
}
