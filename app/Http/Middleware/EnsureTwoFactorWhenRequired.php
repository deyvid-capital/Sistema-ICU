<?php

namespace App\Http\Middleware;

use App\Filament\Pages\Auth\SetupTwoFactor;
use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a los usuarios marcados con `requiere_2fa` a configurar y confirmar su
 * autenticación de dos factores antes de usar el resto del panel. Mientras no la
 * confirmen, se les redirige a la pantalla dedicada de enrolamiento.
 */
class EnsureTwoFactorWhenRequired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user() ?? $request->user();

        if ($user instanceof User
            && $user->requiere_2fa
            && ! $user->hasConfirmedTwoFactor()
            && ! $this->rutaPermitida($request)) {

            return redirect(SetupTwoFactor::getUrl());
        }

        return $next($request);
    }

    /**
     * Rutas permitidas mientras el enrolamiento está pendiente: la propia
     * pantalla de setup, logout y peticiones internas de Livewire.
     * Mi perfil queda bloqueado para que no se pueda eludir el wizard.
     */
    protected function rutaPermitida(Request $request): bool
    {
        return $request->routeIs(
            'filament.admin.auth.setup-two-factor',
            'filament.admin.auth.logout',
            '*livewire.update',
            '*livewire.upload-file',
        );
    }
}
