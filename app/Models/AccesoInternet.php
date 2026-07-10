<?php

namespace App\Models;

use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccesoInternet extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TogglesEstadoOnSoftDelete;

    protected $table = 'internet';

    protected $fillable = [
        'usuario_id',
        'usuario_red',
        'nivel_acceso',
        'fortitoken',
        'estado',
        'fecha',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function accesosVirtual(): HasMany
    {
        return $this->hasMany(AccesoVirtual::class, 'internet_id');
    }
}
