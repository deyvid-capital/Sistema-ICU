<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fondo extends Model
{
    use HasFactory;

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
