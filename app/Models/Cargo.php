<?php

namespace App\Models;

use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Database\Factories\CargoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    /** @use HasFactory<CargoFactory> */
    use HasFactory;

    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['users', 'movimientos'];

    protected $primaryKey = 'id_cargo';

    protected $fillable = [
        'area_id',
        'cargo',
        'descripcion',
    ];

    /**
     * Get the users for the position.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_cargo', 'id_cargo');
    }

    /**
     * Get the area that the position belongs to.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id', 'id_area');
    }

    /**
     * Get the movements for the position.
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'cargo_id', 'id_cargo');
    }
}
