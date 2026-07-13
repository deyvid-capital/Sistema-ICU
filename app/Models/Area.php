<?php

namespace App\Models;

use Database\Factories\AreaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    /** @use HasFactory<AreaFactory> */
    use HasFactory;

    protected $primaryKey = 'id_area';

    protected $fillable = [
        'area',
        'descripcion',
    ];

    /**
     * Get the users for the area.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_area', 'id_area');
    }

    /**
     * Get the software areas for the area.
     */
    public function softwareAreas(): HasMany
    {
        return $this->hasMany(SoftwareArea::class, 'area_id', 'id_area');
    }

    /**
     * Get the positions that belong to the area.
     */
    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class, 'area_id', 'id_area');
    }
}
