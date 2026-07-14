<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeloLaptop extends Model
{
    use HasFactory;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['equipos'];

    protected $table = 'modelos_laptops';

    protected $fillable = [
        'nombre',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'modelo_id');
    }
}
