<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a los usuarios marcados con `requiere_2fa` a configurar y confirmar su
 * autenticación de dos factores antes de usar el resto del panel. Mientras no la
 * confirmen, se les redirige a "Mi perfil" (donde está la sección de 2FA).
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

            Notification::make()
                ->warning()
                ->title('Debes activar la verificación en dos pasos')
                ->body('Un administrador requiere que actives el 2FA antes de continuar.')
                ->persistent()
                ->send();

            return redirect(MyProfilePage::getUrl(panel: 'admin'));
        }

        return $next($request);
    }

    /**
     * Rutas que no deben redirigirse: el propio perfil (donde se activa el 2FA),
     * el logout y las peticiones internas de Livewire (para que el formulario funcione).
     */
    protected function rutaPermitida(Request $request): bool
    {
        return $request->routeIs(
            'filament.admin.pages.mi-perfil',
            'filament.admin.auth.logout',
            '*livewire.update',
            '*livewire.upload-file',
        );
    }
}
