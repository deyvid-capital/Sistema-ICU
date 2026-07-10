<?php

namespace App\Models;

use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccesoDaza extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TogglesEstadoOnSoftDelete;

    protected $table = 'accesos_daza';

    protected $fillable = [
        'usuario_id',
        'usuario_daza',
        'nivel_acceso',
        'fondo_id',
        'ambiente',
        'estado',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function fondo(): BelongsTo
    {
        return $this->belongsTo(Fondo::class, 'fondo_id', 'id_fondo');
    }
}
