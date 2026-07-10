<?php

namespace App\Models;

use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccesoSharepoint extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TogglesEstadoOnSoftDelete;

    protected $table = 'accesos_sharepoint';

    protected $fillable = [
        'usuario_id',
        'sitio_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function sitio(): BelongsTo
    {
        return $this->belongsTo(SitioSharepoint::class, 'sitio_id');
    }
}
