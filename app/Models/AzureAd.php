<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AzureAd extends Model
{

    protected $table = 'azure_ad';

    protected $fillable = [
        'usuario_id',
        'iniciales',
        'sincronizacion',
        'mfa',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'mfa' => 'boolean',
            'estado' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
