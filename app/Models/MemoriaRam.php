<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemoriaRam extends Model
{
    use HasFactory;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['equipos'];

    protected $table = 'memorias_ram';

    protected $fillable = [
        'tipo_ram',
    ];

    public function equipos(): HasMany
    {
        return $this->hasMany(Equipo::class, 'ram_id');
    }
}
