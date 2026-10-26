<?php

namespace App\Services\Firma;

use App\Exceptions\FirmaDigitalException;
use App\Filament\Resources\CertificadosFirma\CertificadoFirmaResource;
use App\Models\CertificadoFirma;
use App\Models\FirmaSetting;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Support\Auditoria\AuditorSeguridad;
use App\Support\Notifications\NotifyAdmins;
use Illuminate\Support\Facades\Hash;
use SensitiveParameter;

/**
 * Antes de firmar: firma activa y vigente, no bloqueada, código 2FA (si la
 * configuración lo exige) y contraseña del sistema. Con eso se desenvuelve la
 * contraseña del .p12 y se lee el archivo, una sola vez por petición.
 */
class AbrirCredencialesFirma
{
    public function __construct(
        private BovedaCertificados $boveda,
        private EnvolturaContrasena $envoltura,
        private AuditorSeguridad $auditor,
    ) {}

    /**
     * @throws FirmaDigitalException
     */
    public function __invoke(User $usuario, #[SensitiveParameter] string $contrasenaSistema, #[SensitiveParameter] ?string $codigoDosPasos = null): CredencialesFirma
    {
        $certificado = $usuario->certificadoFirmaActivo()->first();

        if ($certificado === null || blank($certificado->contrasena_p12_envuelta)) {
            throw FirmaDigitalException::sinFirmaActiva();
        }

        if ($certificado->estaBloqueado()) {
            throw FirmaDigitalException::firmaBloqueada(
                $certificado->bloqueado_hasta->timezone(config('app.display_timezone'))->format('H:i'),
            );
        }

        if (! $certificado->estaVigente()) {
            throw FirmaDigitalException::certificadoVencido($certificado->vigente_hasta?->format('d/m/Y'));
        }

        $this->verificarDosPasos($usuario, $codigoDosPasos);

        if (! Hash::check($contrasenaSistema, $usuario->password)) {
            throw $this->registrarIntentoFallido($certificado, $usuario);
        }

        if ($certificado->intentos_fallidos > 0) {
            $certificado->update(['intentos_fallidos' => 0]);
        }

        return new CredencialesFirma(
            $certificado,
            $this->boveda->leer($certificado->archivo),
            $this->envoltura->desenvolver($certificado->contrasena_p12_envuelta, $contrasenaSistema),
        );
    }

    public static function exigeDosPasos(): bool
    {
        return FirmaSetting::actual()->requiere_2fa_al_firmar;
    }

    /**
     * @throws FirmaDigitalException
     */
    private function verificarDosPasos(User $usuario, #[SensitiveParameter] ?string $codigo): void
    {
        if (! self::exigeDosPasos()) {
            return;
        }

        if (! $usuario->hasConfirmedTwoFactor()) {
            throw FirmaDigitalException::dosPasosNoConfigurado();
        }

        if (blank($codigo) || ! filament('filament-breezy')->verify(code: (string) $codigo, user: $usuario)) {
            throw FirmaDigitalException::codigoDosPasosIncorrecto();
        }
    }

    private function registrarIntentoFallido(CertificadoFirma $certificado, User $usuario): FirmaDigitalException
    {
        $ajustes = FirmaSetting::actual();
        $intentos = $certificado->intentos_fallidos + 1;
        $restantes = max($ajustes->intentos_maximos - $intentos, 0);

        if ($restantes > 0) {
            $certificado->update(['intentos_fallidos' => $intentos]);
            $this->auditor->firmaDigitalIntentoFallido($usuario, $restantes);

            return FirmaDigitalException::contrasenaAlFirmarIncorrecta($restantes);
        }

        $hasta = now()->addMinutes($ajustes->minutos_bloqueo);
        $certificado->update(['intentos_fallidos' => 0, 'bloqueado_hasta' => $hasta]);
        $this->auditor->firmaDigitalBloqueada($usuario, $hasta);

        NotifyAdmins::dispatch(
            title: 'Firma digital bloqueada',
            body: "La firma de {$usuario->name} quedó bloqueada {$ajustes->minutos_bloqueo} minutos por contraseñas incorrectas al firmar.",
            url: CertificadoFirmaResource::getUrl(panel: 'admin'),
            icon: 'heroicon-o-lock-closed',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
            actor: $usuario,
        );

        return FirmaDigitalException::contrasenaAlFirmarIncorrecta(0);
    }
}
