<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Jeffgreco13\FilamentBreezy\Pages\TwoFactorPage;

/**
 * Desafío TOTP post-login. No admite códigos de recuperación.
 */
class TwoFactorChallenge extends TwoFactorPage
{
    public $usingRecoveryCode = false;

    /**
     * @return array<int, TextInput>
     */
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('code')
                ->label(__('filament-breezy::default.fields.2fa_code'))
                ->placeholder(__('filament-breezy::default.two_factor.code_placeholder'))
                ->required()
                ->extraInputAttributes(['class' => 'text-center', 'autocomplete' => 'one-time-code'])
                ->autofocus()
                ->suffixAction(
                    Action::make('cancel')
                        ->tooltip(__('filament-breezy::default.cancel'))
                        ->icon('heroicon-o-x-circle')
                        ->action(function (): void {
                            Filament::auth()->logout();
                            $this->mount();
                        })
                ),
        ];
    }

    public function toggleRecoveryCode(): void
    {
        $this->usingRecoveryCode = false;
    }

    public function updatedUsingRecoveryCode(): void
    {
        $this->usingRecoveryCode = false;
    }

    public function hasValidCode(): bool
    {
        if ($this->usingRecoveryCode) {
            return false;
        }

        return $this->code && filament('filament-breezy')->verify(code: $this->code);
    }
}
