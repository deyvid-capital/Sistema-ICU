<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Software extends Model
{

    protected $primaryKey = 'id_software';

    protected $table = 'softwares';

    protected $fillable = [
        'categoria',
        'nombre_software',
        'estado',
        'fecha_autorizacion',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha_autorizacion' => 'date',
        ];
    }

    public function areas(): HasMany
    {
        return $this->hasMany(SoftwareArea::class, 'software_id', 'id_software');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(SoftwareUsuario::class, 'id_software', 'id_software');
    }

    public function excepciones(): HasMany
    {
        return $this->hasMany(SoftwareExc::class, 'software_id', 'id_software');
    }
}
