<?php

namespace App\Filament\Resources\AzureAds\Schemas;

use App\Filament\Resources\Users\RelationManagers\AzureAdRelationManager;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class AzureAdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cuenta Azure AD')
                    ->description('Datos de sincronización y autenticación del usuario en Azure Active Directory.')
                    ->icon(Heroicon::OutlinedCloud)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(User::class))
                                    ->hiddenOn(AzureAdRelationManager::class)
                                    ->unique(
                                        table: 'cuentas_azure_ad',
                                        column: 'usuario_id',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    )
                                    ->validationMessages([
                                        'unique' => 'Este usuario ya tiene una cuenta Azure AD registrada.',
                                    ]),
                                TextInput::make('iniciales')
                                    ->label('Iniciales')
                                    ->placeholder('Ej. JPP')
                                    ->maxLength(10)
                                    ->required()
                                    ->unique(
                                        table: 'cuentas_azure_ad',
                                        column: 'iniciales',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    )
                                    ->validationMessages([
                                        'unique' => 'Estas iniciales ya están en uso.',
                                    ]),
                                Select::make('sincronizacion')
                                    ->label('Sincronización')
                                    ->native(false)
                                    ->options([
                                        'Nube' => 'Nube',
                                        'Local / Nube' => 'Local / Nube',
                                        'Local / No sincronizado' => 'Local / No sincronizado',
                                    ])
                                    ->default('Local / No sincronizado')
                                    ->required(),
                                Toggle::make('mfa')
                                    ->label('MFA habilitado')
                                    ->helperText('Autenticación multifactor.')
                                    ->default(false),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
