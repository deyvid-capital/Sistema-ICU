<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccesoSharepoint extends Model
{
    use SoftDeletes;


    protected $table = 'accesos_sharepoint';

    public $incrementing = false;

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
