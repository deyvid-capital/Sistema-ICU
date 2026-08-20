<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';

    protected $fillable = [
        'usuario_id',
        'tipo_evento',
        'canal',
        'habilitado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'habilitado' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
