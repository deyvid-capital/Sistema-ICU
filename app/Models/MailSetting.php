<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'activo',
        'mailer',
        'host',
        'port',
        'scheme',
        'encryption',
        'require_tls',
        'username',
        'password',
        'from_address',
        'from_name',
        'ehlo_domain',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'require_tls' => 'boolean',
            'port' => 'integer',
            'password' => 'encrypted',
        ];
    }

    /**
     * Configuración única de correo: siempre se trabaja sobre la primera fila.
     */
    public static function actual(): self
    {
        return static::query()->first() ?? new static([
            'activo' => false,
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'scheme' => config('mail.mailers.smtp.scheme'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'require_tls' => config('mail.mailers.smtp.require_tls'),
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'ehlo_domain' => config('mail.mailers.smtp.local_domain'),
        ]);
    }

    public function tienePassword(): bool
    {
        $raw = $this->getAttributes()['password'] ?? null;

        return filled($raw);
    }
}
