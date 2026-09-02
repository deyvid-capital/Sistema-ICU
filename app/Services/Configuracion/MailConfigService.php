<?php

namespace App\Services\Configuracion;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resuelve la configuración SMTP combinando config/mail.php (.env)
 * con la fila opcional cifrada en mail_settings, y la aplica en runtime.
 */
class MailConfigService
{
    public const CACHE_KEY = 'mail.resuelto';

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $resuelta = null;

    /**
     * @return array<string, mixed>
     */
    public function resolver(): array
    {
        return $this->resuelta ??= Cache::rememberForever(self::CACHE_KEY, fn (): array => $this->construir());
    }

    public function olvidar(): void
    {
        $this->resuelta = null;

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Sobrescribe config('mail.*') para el request/proceso actual y reinicia mailers.
     */
    public function aplicar(): void
    {
        $resolucion = $this->resolver();

        if (! ($resolucion['activo'] ?? false)) {
            return;
        }

        config([
            'mail.default' => $resolucion['mailer'],
            'mail.mailers.smtp.host' => $resolucion['host'],
            'mail.mailers.smtp.port' => $resolucion['port'],
            'mail.mailers.smtp.scheme' => $resolucion['scheme'],
            'mail.mailers.smtp.encryption' => $resolucion['encryption'],
            'mail.mailers.smtp.require_tls' => $resolucion['require_tls'],
            'mail.mailers.smtp.username' => $resolucion['username'],
            'mail.mailers.smtp.password' => $resolucion['password'],
            'mail.mailers.smtp.local_domain' => $resolucion['ehlo_domain'],
            'mail.from.address' => $resolucion['from_address'],
            'mail.from.name' => $resolucion['from_name'],
        ]);

        $this->reiniciarMailers();
    }

    protected function reiniciarMailers(): void
    {
        $mail = Mail::getFacadeRoot();

        if (method_exists($mail, 'purge')) {
            $mail->purge('smtp');
        }

        if (method_exists($mail, 'forgetMailers')) {
            $mail->forgetMailers();
        }
    }

    /**
     * Valores listos para el formulario (sin contraseña).
     *
     * @return array<string, mixed>
     */
    public function paraFormulario(): array
    {
        $resolucion = $this->resolver();

        return [
            'activo' => $resolucion['activo'],
            'mailer' => $resolucion['mailer'],
            'host' => $resolucion['host'],
            'port' => $resolucion['port'],
            'scheme' => $resolucion['scheme'],
            'encryption' => $resolucion['encryption'],
            'require_tls' => $resolucion['require_tls'],
            'username' => $resolucion['username'],
            'from_address' => $resolucion['from_address'],
            'from_name' => $resolucion['from_name'],
            'ehlo_domain' => $resolucion['ehlo_domain'],
            'tiene_password' => $resolucion['tiene_password'],
        ];
    }

    public function tienePasswordGuardada(): bool
    {
        return (bool) ($this->resolver()['tiene_password'] ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function construir(): array
    {
        $fila = $this->leerFila();
        $smtp = config('mail.mailers.smtp', []);

        $passwordDb = null;

        if ($fila?->tienePassword()) {
            $passwordDb = $fila->password;
        }

        return [
            'activo' => (bool) ($fila?->activo ?? false),
            'mailer' => $fila?->mailer ?: config('mail.default'),
            'host' => $fila?->host ?: ($smtp['host'] ?? null),
            'port' => $fila?->port ?: ($smtp['port'] ?? null),
            'scheme' => $fila ? $fila->scheme : ($smtp['scheme'] ?? null),
            'encryption' => $fila ? $fila->encryption : ($smtp['encryption'] ?? null),
            'require_tls' => $fila?->require_tls ?? ($smtp['require_tls'] ?? true),
            'username' => $fila?->username ?: ($smtp['username'] ?? null),
            'password' => $passwordDb !== null ? $passwordDb : ($smtp['password'] ?? null),
            'tiene_password' => $fila?->tienePassword() ?? filled($smtp['password'] ?? null),
            'from_address' => $fila?->from_address ?: config('mail.from.address'),
            'from_name' => $fila?->from_name ?: config('mail.from.name'),
            'ehlo_domain' => $fila?->ehlo_domain ?: ($smtp['local_domain'] ?? null),
        ];
    }

    protected function leerFila(): ?MailSetting
    {
        try {
            if (! Schema::hasTable('mail_settings')) {
                return null;
            }

            return MailSetting::query()->first();
        } catch (Throwable) {
            return null;
        }
    }
}
