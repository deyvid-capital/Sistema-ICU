<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Microprocesador extends Model
{
    use HasFactory;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['equipos'];

    protected $table = 'microprocesadores';

    protected $fillable = [
        'nombre_micro',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'microprocesador_id');
    }
}
