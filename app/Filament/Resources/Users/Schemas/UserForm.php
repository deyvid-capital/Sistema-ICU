<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos personales')
                    ->description('Información básica del usuario.')
                    ->icon(Heroicon::OutlinedUser)
                    ->secondary()
                    ->columns(1)
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextInput::make('name')
                                ->label('Nombre completo')
                                ->placeholder('Ej. Juan Pérez')
                                ->required()
                                ->maxLength(200)
                                ->autocomplete('name'),
                            TextInput::make('carnet')
                                ->label('Carnet')
                                ->placeholder('Ej. 1234 LP')
                                ->maxLength(50),
                        ]),
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            Select::make('id_area')
                                ->label('Área')
                                ->required()
                                ->live()
                                ->options(fn () => Area::query()->orderBy('area')->pluck('area', 'id_area'))
                                ->createOptionForm([
                                    TextInput::make('area')
                                        ->label('Nombre del área')
                                        ->required()
                                        ->maxLength(100),
                                ])
                                ->createOptionUsing(function (array $data): int {
                                    return Area::create(['area' => $data['area']])->id_area;
                                })
                                ->afterStateUpdated(fn (Set $set) => $set('id_cargo', null))
                                ->native(false)
                                ->placeholder('Selecciona un área')
                                ->searchable()
                                ->preload(),
                            Select::make('id_cargo')
                                ->label('Cargo')
                                ->required()
                                ->options(fn (Get $get) => Cargo::query()
                                    ->where('area_id', $get('id_area'))
                                    ->orderBy('cargo')
                                    ->pluck('cargo', 'id_cargo'))
                                ->createOptionForm([
                                    TextInput::make('cargo')
                                        ->label('Nombre del cargo')
                                        ->required()
                                        ->maxLength(150),
                                ])
                                ->createOptionUsing(function (array $data, Get $get): int {
                                    return Cargo::create([
                                        'cargo' => $data['cargo'],
                                        'area_id' => $get('id_area'),
                                    ])->id_cargo;
                                })
                                ->rule(function (Get $get): Closure {
                                    return function (string $attribute, $value, Closure $fail) use ($get): void {
                                        if (blank($value)) {
                                            return;
                                        }

                                        if (! Cargo::query()->where('id_cargo', $value)->where('area_id', $get('id_area'))->exists()) {
                                            $fail('El cargo seleccionado no pertenece al área elegida.');
                                        }
                                    };
                                })
                                ->native(false)
                                ->disabled(fn (Get $get): bool => blank($get('id_area')))
                                ->placeholder('Selecciona primero un área')
                                ->helperText('Selecciona primero un área para ver sus cargos.')
                                ->searchable()
                                ->preload(),
                        ]),
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextInput::make('email')
                                ->label('Correo electrónico')
                                ->placeholder('usuario@empresa.com')
                                ->email()
                                ->required()
                                ->maxLength(100)
                                ->unique(User::class, 'email', ignoreRecord: true)
                                ->autocomplete('email')
                                ->prefixIcon(Heroicon::OutlinedEnvelope),
                            TextInput::make('telefono')
                                ->label('Teléfono')
                                ->placeholder('Ej. 7123-4567')
                                ->tel()
                                ->maxLength(30)
                                ->unique(User::class, 'telefono', ignoreRecord: true)
                                ->nullable()
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                                ->prefixIcon(Heroicon::OutlinedPhone),
                        ]),
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextInput::make('password')
                                ->label(fn ($livewire) => $livewire instanceof CreateRecord
                                    ? 'Contraseña'
                                    : 'Nueva contraseña (opcional)')
                                ->password()
                                ->revealable()
                                ->live(onBlur: true)
                                ->minLength(8)
                                ->dehydrated(fn ($state) => filled($state))
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                ->helperText('Mínimo 8 caracteres.'),
                            Toggle::make('tipo')
                                ->label('Tipo')
                                ->onIcon(Heroicon::Bolt)
                                ->offIcon(Heroicon::BoltSlash)
                                ->onColor('success')
                                ->offColor('danger')
                                ->default(true)
                                ->helperText('Alta o baja el usuario , se creará un movimiento en el sistema.'),
                        ]),

                    ]),
            ]);
    }
}
