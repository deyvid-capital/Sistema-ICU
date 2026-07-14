<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TarjetaVideo extends Model
{
    use HasFactory;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['equipos'];

    protected $table = 'tarjetas_video';

    protected $fillable = [
        'nombre_gpu',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'gpu_id');
    }
}
