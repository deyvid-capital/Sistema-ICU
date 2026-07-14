<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sistema extends Model
{
    use HasFactory;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['accesos'];

    protected $primaryKey = 'id_sistema';

    protected $fillable = [
        'nombre_sistema',
        'descripcion',
    ];

    public function accesos(): HasMany
    {
        return $this->hasMany(AccesoSistema::class, 'id_sistema', 'id_sistema');
    }
}
