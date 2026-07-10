<?php

namespace App\Models;

use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AzureAd extends Model
{
    use HasFactory, SoftDeletes, TogglesEstadoOnSoftDelete;

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
