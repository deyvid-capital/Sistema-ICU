<?php

namespace App\Support\Auth;

use App\Filament\Resources\Users\UserResource;
use App\Models\PasswordResetChallenge;
use App\Models\User;
use App\Notifications\CodigoRecuperacionPassword;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;
use App\Support\Sessions\UserSessions;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Recuperación de contraseña con código OTP alfanumérico, caducidad,
 * límite de intentos y bloqueo temporal (backend).
 */
class RecuperacionPassword
{
    public const MENSAJE_GENERICO = 'Si ese correo está registrado y puede recuperarse, te enviaremos un código. Revisa tu bandeja de entrada y el correo no deseado.';

    public function solicitar(string $email, ?string $ip = null): void
    {
        $email = $this->normalizarEmail($email);
        $desafio = PasswordResetChallenge::query()->where('email', $email)->first();

        if ($desafio?->estaBloqueado()) {
            return;
        }

        if ($desafio !== null && $this->segundosReenvio($desafio) > 0) {
            return;
        }

        $usuario = $this->buscarUsuario($email);

        if ($usuario === null) {
            Hash::make($this->generarCodigo());

            return;
        }

        $codigo = $this->generarCodigo();

        DB::transaction(function () use ($desafio, $email, $codigo, $ip, $usuario): void {
            $datos = [
                'email' => $email,
                'code_hash' => Hash::make($codigo),
                'expires_at' => now()->addMinutes($this->codigoTtlMinutos()),
                'enviado_at' => now(),
                'ip' => $ip,
            ];

            if ($desafio === null) {
                $datos['intentos_fallidos'] = 0;
                $datos['bloqueado_hasta'] = null;
                PasswordResetChallenge::query()->create($datos);
            } else {
                $desafio->forceFill($datos)->save();
            }

            $usuario->notify((new CodigoRecuperacionPassword(
                codigo: $codigo,
                minutos: $this->codigoTtlMinutos(),
                expiraEn: now()->addMinutes($this->codigoTtlMinutos()),
                urlRestablecer: $this->urlRestablecer($email),
            ))->afterCommit());
        });

        $this->avisarAdmins(
            $usuario,
            'Solicitud de restablecimiento de contraseña',
            "{$usuario->name} ({$usuario->email}) pidió un código para restablecer su contraseña.".($ip !== null ? " IP: {$ip}." : ''),
            'heroicon-o-envelope',
            'info',
        );
    }

    public function restablecer(string $email, string $codigo, string $password): ResultadoRestablecer
    {
        $email = $this->normalizarEmail($email);
        $codigo = $this->normalizarCodigo($codigo);
        $desafio = PasswordResetChallenge::query()->where('email', $email)->first();

        if ($desafio?->estaBloqueado()) {
            return new ResultadoRestablecer(
                ok: false,
                mensaje: $this->mensajeBloqueo($desafio->bloqueado_hasta),
                bloqueado: true,
                bloqueadoHasta: $desafio->bloqueado_hasta,
            );
        }

        $hash = filled($desafio?->code_hash) ? $desafio->code_hash : $this->hashDummy();
        $vigente = $desafio?->codigoEstaVigente() ?? false;
        $correcto = $vigente && Hash::check($codigo, $hash);

        if (! $correcto) {
            if (! $vigente) {
                Hash::check($codigo, $this->hashDummy());

                return new ResultadoRestablecer(
                    ok: false,
                    mensaje: 'El código no es válido o ya caducó. Solicita uno nuevo si es necesario.',
                );
            }

            return $this->registrarFallo($desafio);
        }

        $usuario = $this->buscarUsuario($email);

        if ($usuario === null) {
            $desafio->delete();

            return new ResultadoRestablecer(
                ok: false,
                mensaje: 'El código no es válido o ya caducó. Solicita uno nuevo si es necesario.',
            );
        }

        $usuario->forceFill([
            'password' => $password,
        ])->save();

        $usuario->desbloquear();
        UserSessions::terminate($usuario);
        $desafio->delete();

        event(new PasswordReset($usuario));

        $this->avisarAdmins(
            $usuario,
            'Contraseña restablecida',
            "{$usuario->name} ({$usuario->email}) restableció su contraseña con el código de recuperación.",
            'heroicon-o-key',
            'success',
        );

        return new ResultadoRestablecer(
            ok: true,
            mensaje: 'Tu contraseña se actualizó. Ya puedes iniciar sesión con la nueva.',
        );
    }

    public function estado(string $email): EstadoRecuperacion
    {
        $email = $this->normalizarEmail($email);

        if ($email === '') {
            return EstadoRecuperacion::vacio();
        }

        $desafio = PasswordResetChallenge::query()->where('email', $email)->first();

        if ($desafio === null) {
            return EstadoRecuperacion::vacio();
        }

        $bloqueado = $desafio->estaBloqueado();

        return new EstadoRecuperacion(
            bloqueado: $bloqueado,
            bloqueadoHasta: $bloqueado ? $desafio->bloqueado_hasta : null,
            segundosBloqueo: $this->segundosHasta($bloqueado ? $desafio->bloqueado_hasta : null),
            segundosExpiracionCodigo: $desafio->codigoEstaVigente()
                ? $this->segundosHasta($desafio->expires_at)
                : 0,
            segundosReenvio: $this->segundosReenvio($desafio),
            intentosRestantes: max(0, $this->maxIntentos() - $desafio->intentos_fallidos),
            tieneCodigoActivo: $desafio->codigoEstaVigente(),
        );
    }

    public function urlRestablecer(string $email): string
    {
        $email = $this->normalizarEmail($email);
        $panel = Filament::getCurrentOrDefaultPanel();
        $minutos = $this->codigoTtlMinutos() + $this->bloqueoMinutos();

        return URL::temporarySignedRoute(
            $panel->generateRouteName('auth.password-reset.reset'),
            now()->addMinutes($minutos),
            [
                'email' => $email,
                'token' => 'otp',
            ],
        );
    }

    public function formatearCodigo(string $codigo): string
    {
        $codigo = $this->normalizarCodigo($codigo);

        if (strlen($codigo) === 8) {
            return substr($codigo, 0, 4).'-'.substr($codigo, 4);
        }

        return $codigo;
    }

    public function normalizarEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    public function normalizarCodigo(string $codigo): string
    {
        return Str::upper((string) preg_replace('/[^A-Za-z0-9]/', '', $codigo));
    }

    private function registrarFallo(PasswordResetChallenge $desafio): ResultadoRestablecer
    {
        $desafio->increment('intentos_fallidos');
        $desafio->refresh();

        $restantes = $this->maxIntentos() - $desafio->intentos_fallidos;

        if ($restantes > 0) {
            $texto = $restantes === 1
                ? 'Código incorrecto. Te queda 1 intento antes de bloquear la recuperación.'
                : "Código incorrecto. Te quedan {$restantes} intentos antes de bloquear la recuperación.";

            return new ResultadoRestablecer(
                ok: false,
                mensaje: $texto,
                intentosRestantes: $restantes,
            );
        }

        $hasta = now()->addMinutes($this->bloqueoMinutos());
        $desafio->forceFill([
            'bloqueado_hasta' => $hasta,
            'code_hash' => '',
        ])->save();

        $usuario = $this->buscarUsuario($desafio->email);

        if ($usuario !== null) {
            $this->avisarAdmins(
                $usuario,
                'Código de recuperación incorrecto',
                "{$usuario->name} ({$usuario->email}) introdujo un código incorrecto {$this->maxIntentos()} veces. La recuperación quedó bloqueada hasta ".$hasta->timezone(config('app.display_timezone'))->format('d/m/Y H:i').'.',
                'heroicon-o-exclamation-triangle',
                'danger',
            );
        }

        return new ResultadoRestablecer(
            ok: false,
            mensaje: $this->mensajeBloqueo($hasta),
            bloqueado: true,
            bloqueadoHasta: $hasta,
            intentosRestantes: 0,
        );
    }

    private function avisarAdmins(User $usuario, string $titulo, string $cuerpo, string $icono, string $color): void
    {
        $url = null;

        try {
            $url = UserResource::getUrl('seguridad', ['record' => $usuario], panel: 'admin');
        } catch (\Throwable) {
            $url = null;
        }

        NotifyAdmins::dispatch(
            title: $titulo,
            body: $cuerpo,
            url: $url,
            icon: $icono,
            color: $color,
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
            actor: $usuario,
        );
    }

    private function buscarUsuario(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    private function generarCodigo(): string
    {
        $alfabeto = (string) config('acceso.recuperacion.alfabeto', 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789');
        $longitud = (int) config('acceso.recuperacion.longitud_codigo', 8);
        $max = strlen($alfabeto) - 1;

        do {
            $codigo = '';
            $tieneLetra = false;
            $tieneNumero = false;

            for ($i = 0; $i < $longitud; $i++) {
                $caracter = $alfabeto[random_int(0, $max)];
                $codigo .= $caracter;

                if (ctype_alpha($caracter)) {
                    $tieneLetra = true;
                }

                if (ctype_digit($caracter)) {
                    $tieneNumero = true;
                }
            }
        } while (! $tieneLetra || ! $tieneNumero);

        return $codigo;
    }

    private function segundosReenvio(PasswordResetChallenge $desafio): int
    {
        if ($desafio->estaBloqueado()) {
            return $this->segundosHasta($desafio->bloqueado_hasta);
        }

        $disponible = $desafio->enviado_at->copy()->addSeconds($this->reenvioCooldownSegundos());

        return $this->segundosHasta($disponible);
    }

    private function segundosHasta(?Carbon $momento): int
    {
        if ($momento === null || $momento->isPast()) {
            return 0;
        }

        return max(0, $momento->getTimestamp() - now()->getTimestamp());
    }

    private function mensajeBloqueo(Carbon $hasta): string
    {
        $hora = $hasta->timezone(config('app.display_timezone'))->format('H:i');

        return "Demasiados códigos incorrectos. No puedes validar ni pedir un código nuevo hasta las {$hora}.";
    }

    private function hashDummy(): string
    {
        return Hash::make('codigo-invalido');
    }

    private function codigoTtlMinutos(): int
    {
        return (int) config('acceso.recuperacion.codigo_ttl_minutos', 10);
    }

    private function maxIntentos(): int
    {
        return (int) config('acceso.recuperacion.max_intentos_codigo', 5);
    }

    private function bloqueoMinutos(): int
    {
        return (int) config('acceso.recuperacion.bloqueo_minutos', 15);
    }

    private function reenvioCooldownSegundos(): int
    {
        return (int) config('acceso.recuperacion.reenvio_cooldown_segundos', 60);
    }
}
