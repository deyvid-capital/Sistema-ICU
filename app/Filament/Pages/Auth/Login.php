<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

/**
 * Login del panel con bloqueo por intentos fallidos persistido por cuenta y
 * mensajes claros para cada motivo de rechazo.
 *
 * Se distingue entre "credenciales incorrectas" (suma intento y avisa cuántos
 * quedan) y "credenciales correctas pero sin acceso al panel" (no suma intento,
 * explica el motivo: deshabilitado, vencido, sin rol...).
 */
class Login extends BaseLogin
{
    /**
     * True cuando canAccessPanel() rechazó a un usuario cuya contraseña sí era válida.
     */
    protected bool $accesoDenegadoConCredencialesValidas = false;

    /**
     * Motivo del rechazo cuando las credenciales eran válidas.
     */
    protected ?string $motivoDenegacion = null;

    /**
     * Usuario que superó la validación de credenciales pero no el acceso al panel.
     */
    protected ?User $usuarioRechazado = null;

    public function authenticate(): ?LoginResponse
    {
        $usuario = $this->resolverUsuario();

        // Cuenta ya bloqueada: cortar antes de validar credenciales.
        if ($usuario?->estaBloqueado()) {
            $mensaje = 'Tu cuenta está bloqueada por intentos fallidos. Contacta a un administrador para desbloquearla.';

            app(AuditorSeguridad::class)->intentoConCuentaBloqueada($usuario, (string) ($this->data['email'] ?? ''));

            $this->notificarError('Cuenta bloqueada', $mensaje);

            throw ValidationException::withMessages(['data.email' => $mensaje]);
        }

        return parent::authenticate();
    }

    protected function isUserAllowedToAccessPanel(Authenticatable $user): bool
    {
        $permitido = parent::isUserAllowedToAccessPanel($user);

        if (! $permitido) {
            $this->accesoDenegadoConCredencialesValidas = true;

            if ($user instanceof User) {
                $this->usuarioRechazado = $user;
                $this->motivoDenegacion = $user->motivoAccesoDenegado();
            }
        }

        return $permitido;
    }

    /**
     * Cada rama deja constancia en la auditoría antes de abortar.
     *
     * Filament llama a este método dentro de su `Timebox`, que iguala la
     * duración de las respuestas para no revelar si la cuenta existe. Un único
     * INSERT por rama es despreciable frente a ese margen; la excepción es el
     * bloqueo, que escribe dos, pero ese estado ya lo delata el propio mensaje.
     */
    protected function throwFailureValidationException(): never
    {
        // Caso 1: contraseña correcta pero el panel lo rechazó. No cuenta intento.
        if ($this->accesoDenegadoConCredencialesValidas) {
            $mensaje = $this->motivoDenegacion
                ?? 'No tienes acceso al panel. Verifica tu habilitación con un administrador.';

            app(AuditorSeguridad::class)->accesoDenegado(
                $this->usuarioRechazado,
                $this->motivoDenegacion,
                (string) ($this->data['email'] ?? ''),
            );

            $this->notificarError('Acceso denegado', $mensaje);

            throw ValidationException::withMessages(['data.email' => $mensaje]);
        }

        // Caso 2: contraseña incorrecta sobre una cuenta existente. Cuenta intento.
        $usuario = $this->resolverUsuario();

        if ($usuario !== null) {
            $usuario->registrarIntentoFallido();

            $restantes = max(0, (int) config('acceso.max_intentos_fallidos', 5) - $usuario->intentos_fallidos);

            $auditor = app(AuditorSeguridad::class);
            $auditor->passwordIncorrecta($usuario, $restantes);

            if ($usuario->estaBloqueado()) {
                $auditor->cuentaBloqueada($usuario);

                $mensaje = 'Tu cuenta ha sido bloqueada por demasiados intentos fallidos. Contacta a un administrador.';
                $this->notificarError('Cuenta bloqueada', $mensaje);

                throw ValidationException::withMessages(['data.email' => $mensaje]);
            }

            $mensaje = $restantes === 1
                ? 'Credenciales incorrectas. Te queda 1 intento antes de que se bloquee la cuenta.'
                : "Credenciales incorrectas. Te quedan {$restantes} intentos antes de que se bloquee la cuenta.";
            $this->notificarError('Credenciales incorrectas', $mensaje);

            throw ValidationException::withMessages(['data.email' => $mensaje]);
        }

        // Correo desconocido: mensaje genérico, sin revelar si la cuenta existe.
        app(AuditorSeguridad::class)->correoDesconocido((string) ($this->data['email'] ?? ''));

        parent::throwFailureValidationException();
    }

    protected function notificarError(string $titulo, string $cuerpo): void
    {
        Notification::make()
            ->danger()
            ->title($titulo)
            ->body($cuerpo)
            ->send();
    }

    protected function resolverUsuario(): ?User
    {
        $email = $this->data['email'] ?? null;

        return filled($email)
            ? User::query()->where('email', $email)->first()
            : null;
    }
}
