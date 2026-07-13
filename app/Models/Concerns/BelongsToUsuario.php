<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToUsuario
{
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id')->withTrashed();
    }
}
