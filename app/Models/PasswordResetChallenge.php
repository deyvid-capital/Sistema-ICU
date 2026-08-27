<?php

namespace App\Models;

use Database\Factories\PasswordResetChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'code_hash', 'expires_at', 'intentos_fallidos', 'bloqueado_hasta', 'enviado_at', 'ip'])]
class PasswordResetChallenge extends Model
{
    /** @use HasFactory<PasswordResetChallengeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'bloqueado_hasta' => 'datetime',
            'enviado_at' => 'datetime',
            'intentos_fallidos' => 'integer',
        ];
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }

    public function codigoEstaVigente(): bool
    {
        return $this->code_hash !== '' && $this->expires_at->isFuture();
    }
}
