<?php

namespace App\Livewire\Profile;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Jeffgreco13\FilamentBreezy\Actions\PasswordButtonAction;
use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication as BreezyTwoFactorAuthentication;

/**
 * En "Mi perfil", solo administradores pueden desactivar el 2FA.
 * El resto puede activarlo/confirmarlo, pero no quitarlo (eso lo hace un admin
 * desde Seguridad del usuario). Los códigos de recuperación no se exponen.
 */
class TwoFactorAuthentication extends BreezyTwoFactorAuthentication
{
    public bool $showRecoveryCodes = false;

    /**
     * Keep Livewire's component name. Breezy maps this to the class basename,
     * which would collide with the vendor alias.
     */
    public function getName()
    {
        return $this->__name;
    }

    public function disableAction(): Action
    {
        return PasswordButtonAction::make('disable')
            ->label(__('filament-breezy::default.profile.2fa.actions.disable'))
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (): bool => $this->puedeDesactivarDosFactores())
            ->action(function (): void {
                abort_unless($this->puedeDesactivarDosFactores(), 403);

                $this->user->disableTwoFactorAuthentication();

                Notification::make()
                    ->warning()
                    ->title(__('filament-breezy::default.profile.2fa.disabling.notify'))
                    ->send();
            });
    }

    public function regenerateCodesAction(): Action
    {
        return PasswordButtonAction::make('regenerateCodes')
            ->label(__('filament-breezy::default.profile.2fa.actions.regenerate_codes'))
            ->visible(false)
            ->action(function (): void {
                abort(403);
            });
    }

    /**
     * @return Collection<int, string>
     */
    public function getRecoveryCodesProperty(): Collection
    {
        return collect();
    }

    public function toggleRecoveryCodes(): void
    {
        $this->showRecoveryCodes = false;
    }

    public function updatedShowRecoveryCodes(): void
    {
        $this->showRecoveryCodes = false;
    }

    public function puedeDesactivarDosFactores(): bool
    {
        return (bool) $this->user?->hasAnyRole(['super_admin', 'admin_ti', 'admin']);
    }
}
