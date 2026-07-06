<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UsuarioExterno extends Model
{

    protected $table = 'usuarios_externos';

    protected $fillable = [
        'nombre',
        'carnet',
        'empresa',
        'cargo',
    ];

    public function notasSalida(): HasMany
    {
        return $this->hasMany(NotaSalidaEquipo::class, 'usuario_externo_id');
    }

    public function notasRecepcion(): HasMany
    {
        return $this->hasMany(NotaRecepcionEquipo::class, 'usuarios_externo_id');
    }
}
