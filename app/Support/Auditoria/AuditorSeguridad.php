<?php

namespace App\Support\Auditoria;

use App\Enums\DominioActividad;
use App\Enums\EventoSeguridad;
use App\Models\User;
use App\Support\Auditoria\Concerns\CapturaContextoPeticion;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Escribe los eventos de autenticación y seguridad en `activity_log` bajo el
 * dominio "seguridad".
 *
 * El correo se guarda siempre en las propiedades, incluso cuando hay sujeto:
 * así la columna "Correo" del panel es uniforme y el dato sobrevive al borrado
 * del usuario.
 */
class AuditorSeguridad
{
    use CapturaContextoPeticion;

    /**
     * Claves que nunca se persisten, venga de donde venga la llamada. Es una red
     * de seguridad ante un descuido en un call site, no la única defensa.
     *
     * @var list<string>
     */
    private const PROHIBIDAS = [
        'password',
        'password_confirmation',
        'contrasena',
        'code',
        'codigo',
        'code_hash',
        'otp',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @param  array<string, mixed>  $propiedades
     */
    public function registrar(
        EventoSeguridad $evento,
        ?User $sujeto = null,
        ?User $causante = null,
        ?string $email = null,
        array $propiedades = [],
    ): void {
        $causante ??= $sujeto;
        $email ??= $sujeto?->email;

        $propiedades = Arr::except(
            array_merge(
                $this->contextoPeticion(),
                ['email' => $this->normalizarEmail($email)],
                $propiedades,
            ),
            self::PROHIBIDAS,
        );

        $registro = activity(DominioActividad::Seguridad->value)
            ->event($evento->value)
            ->withProperties($propiedades);

        if ($sujeto !== null) {
            $registro->performedOn($sujeto);
        }

        // Sin esto, ActivityLogger inyecta al usuario autenticado como causante y
        // un intento con correo desconocido quedaría atribuido a esa sesión.
        $causante !== null
            ? $registro->causedBy($causante)
            : $registro->causedByAnonymous();

        $registro->log($evento->getLabel());
    }

    public function ingresoExitoso(User $usuario): void
    {
        $this->registrar(EventoSeguridad::IngresoExitoso, sujeto: $usuario);
    }

    public function passwordIncorrecta(User $usuario, int $intentosRestantes): void
    {
        $this->registrar(EventoSeguridad::PasswordIncorrecta, sujeto: $usuario, propiedades: [
            'intentos_restantes' => $intentosRestantes,
        ]);
    }

    public function correoDesconocido(string $email): void
    {
        $this->registrar(EventoSeguridad::CorreoDesconocido, email: $email);
    }

    public function accesoDenegado(?User $usuario, ?string $motivo, ?string $email = null): void
    {
        $this->registrar(EventoSeguridad::AccesoDenegado, sujeto: $usuario, email: $email, propiedades: [
            'motivo' => $motivo,
        ]);
    }

    public function intentoConCuentaBloqueada(?User $usuario, string $email): void
    {
        $this->registrar(EventoSeguridad::IntentoConCuentaBloqueada, sujeto: $usuario, email: $email);
    }

    public function cuentaBloqueada(User $usuario): void
    {
        $this->registrar(EventoSeguridad::CuentaBloqueada, sujeto: $usuario);
    }

    public function cierreSesion(User $usuario): void
    {
        $this->registrar(EventoSeguridad::CierreSesion, sujeto: $usuario);
    }

    public function otpSolicitado(User $usuario, ?string $ip = null): void
    {
        $this->registrar(EventoSeguridad::OtpSolicitado, sujeto: $usuario, propiedades: array_filter([
            'ip' => $ip,
        ], fn (mixed $valor): bool => filled($valor)));
    }

    public function otpIncorrecto(string $email, int $intentosRestantes, ?User $usuario = null): void
    {
        $this->registrar(EventoSeguridad::OtpIncorrecto, sujeto: $usuario, email: $email, propiedades: [
            'intentos_restantes' => $intentosRestantes,
        ]);
    }

    public function otpBloqueado(string $email, ?CarbonInterface $hasta = null, ?User $usuario = null): void
    {
        $this->registrar(EventoSeguridad::OtpBloqueado, sujeto: $usuario, email: $email, propiedades: [
            'bloqueado_hasta' => $hasta?->toDateTimeString(),
        ]);
    }

    public function passwordRestablecida(User $usuario): void
    {
        $this->registrar(EventoSeguridad::PasswordRestablecida, sujeto: $usuario);
    }

    public function dosFactoresConfirmado(User $usuario): void
    {
        $this->registrar(EventoSeguridad::DosFactoresConfirmado, sujeto: $usuario);
    }

    public function dosFactoresCodigoIncorrecto(User $usuario): void
    {
        $this->registrar(EventoSeguridad::DosFactoresCodigoIncorrecto, sujeto: $usuario);
    }

    /**
     * Lo ejecuta un administrador sobre otra cuenta, así que el causante es él y
     * el sujeto es el usuario afectado.
     */
    public function dosFactoresReseteado(User $objetivo, ?User $administrador = null): void
    {
        $administrador ??= $this->usuarioAutenticado();

        $this->registrar(
            EventoSeguridad::DosFactoresReseteado,
            sujeto: $objetivo,
            // Sin administrador identificado no se atribuye a nadie: el fallback
            // por defecto culparía al propio usuario afectado.
            causante: $administrador ?? $objetivo,
            propiedades: ['administrador' => $administrador?->name],
        );
    }

    private function usuarioAutenticado(): ?User
    {
        $usuario = auth()->user();

        return $usuario instanceof User ? $usuario : null;
    }

    private function normalizarEmail(?string $email): ?string
    {
        if (blank($email)) {
            return null;
        }

        return Str::limit(Str::lower(trim($email)), 254, '');
    }
}
