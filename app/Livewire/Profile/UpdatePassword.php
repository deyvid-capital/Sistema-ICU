<?php

namespace App\Livewire\Profile;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
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
                    ->helperText('Confirme que es usted antes de establecer una contraseña nueva.')
                    ->visible(filament('filament-breezy')->getPasswordUpdateRequiresCurrent()),
                TextInput::make('new_password')
                    ->label(__('filament-breezy::default.fields.new_password'))
                    ->password()
                    ->revealable()
                    ->rules(filament('filament-breezy')->getPasswordUpdateRules())
                    ->required()
                    ->helperText('Mínimo 8 caracteres, con mayúsculas, minúsculas y al menos un número.'),
                TextInput::make('new_password_confirmation')
                    ->label(__('filament-breezy::default.fields.new_password_confirmation'))
                    ->password()
                    ->revealable()
                    ->same('new_password')
                    ->required()
                    ->helperText('Repita la nueva contraseña para evitar errores de tipeo.'),
            ])
            ->statePath('data');
    }
}
