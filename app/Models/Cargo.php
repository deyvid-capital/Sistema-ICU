<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    /** @use HasFactory<\Database\Factories\CargoFactory> */
    use HasFactory;

    protected $primaryKey = 'id_cargo';

    protected $fillable = [
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
     * Get the movements for the position.
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'cargo_id', 'id_cargo');
    }
}
