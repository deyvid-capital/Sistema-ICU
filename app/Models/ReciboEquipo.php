<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReciboEquipo extends Model
{
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['equipo'];

    protected $table = 'recibo_equipo';

    protected $fillable = [
        'equipo_id',
        'fecha',
        'url',
        'observaciones',
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
}
