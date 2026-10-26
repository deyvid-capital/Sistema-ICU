<?php

namespace App\Livewire\Profile;

use App\Services\Firma\RecifrarCertificadoFirma;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword as BreezyUpdatePassword;

class UpdatePassword extends BreezyUpdatePassword
{
    /**
     * Keep Livewire's component name. Breezy maps this to the class basename,
     * which collides with the vendor `update_password` alias.
     */
    public function getName()
    {
        return $this->__name;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('current_password')
                    ->label(__('filament-breezy::default.password_confirm.current_password'))
                    ->required()
                    ->password()
                    ->revealable()
                    ->rule('current_password')
                    ->visible(filament('filament-breezy')->getPasswordUpdateRequiresCurrent()),
                TextInput::make('new_password')
                    ->label(__('filament-breezy::default.fields.new_password'))
                    ->password()
                    ->revealable()
                    ->rules(filament('filament-breezy')->getPasswordUpdateRules())
                    ->required(),
                TextInput::make('new_password_confirmation')
                    ->label(__('filament-breezy::default.fields.new_password_confirmation'))
                    ->password()
                    ->revealable()
                    ->same('new_password')
                    ->required(),
            ])
            ->statePath('data');
    }

    /**
     * Igual que Breezy, pero vuelve a envolver la contraseña del .p12 con la nueva
     * contraseña en la misma transacción, para que la firma digital siga activa.
     */
    public function submit(): void
    {
        $datos = $this->form->getState();
        $nueva = (string) $datos['new_password'];
        $hashNuevo = Hash::make($nueva);

        DB::transaction(function () use ($datos, $nueva, $hashNuevo): void {
            app(RecifrarCertificadoFirma::class)($this->user, (string) ($datos['current_password'] ?? ''), $nueva, $hashNuevo);

            $this->user->forceFill([
                'password' => $hashNuevo,
                'remember_token' => Str::random(60),
            ])->save();
        });

        session()->forget('password_hash_'.Auth::getDefaultDriver());
        $this->reset(['data']);

        Notification::make()
            ->success()
            ->title(__('filament-breezy::default.profile.password.notify'))
            ->send();
    }
}
