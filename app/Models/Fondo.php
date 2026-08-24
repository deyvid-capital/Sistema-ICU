<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fondo extends Model
{
    use HasFactory;
    use LogsIcuActivity;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['accesosDaza'];

    protected $primaryKey = 'id_fondo';

    protected $fillable = [
        'codigo',
        'nombre_completo',
        'descripcion',
    ];

    public function accesosDaza(): HasMany
    {
        return $this->hasMany(AccesoDaza::class, 'fondo_id', 'id_fondo');
    }
}
