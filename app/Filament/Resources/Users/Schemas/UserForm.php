<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Hidden;
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
                            TextInput::make('nombres')
                                ->label('Nombres')
                                ->placeholder('Ej. Juan Carlos')
                                ->required()
                                ->maxLength(100)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncName($get, $set))
                                ->autocomplete('given-name'),
                            TextInput::make('apellidos')
                                ->label('Apellidos')
                                ->placeholder('Ej. Pérez López')
                                ->required()
                                ->maxLength(100)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Get $get, Set $set) => static::syncName($get, $set))
                                ->autocomplete('family-name'),
                            TextInput::make('carnet')
                                ->label('Carnet')
                                ->placeholder('Ej. 1234 LP')
                                ->maxLength(50),
                            TextInput::make('email')
                                ->label('Correo electrónico')
                                ->placeholder('usuario@empresa.com')
                                ->email()
                                ->required()
                                ->maxLength(100)
                                ->unique(User::class, 'email', ignoreRecord: true)
                                ->autocomplete('email')
                                ->prefixIcon(Heroicon::OutlinedEnvelope),
                                ]),
                        Hidden::make('name')
                            ->dehydrateStateUsing(fn (Get $get): string => static::fullName($get('nombres'), $get('apellidos'))),
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
                            TextInput::make('telefono')
                                ->label('Teléfono')
                                ->placeholder('Ej. 7123-4567')
                                ->tel()
                                ->maxLength(30)
                                ->unique(User::class, 'telefono', ignoreRecord: true)
                                ->nullable()
                                ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                                ->prefixIcon(Heroicon::OutlinedPhone),
                            TextInput::make('password')
                                ->label(fn ($livewire) => $livewire instanceof CreateRecord
                                    ? 'Contraseña'
                                    : 'Nueva contraseña (opcional)')
                                ->password()
                                ->revealable()
                                ->required(fn ($livewire) => $livewire instanceof CreateRecord)
                                ->live(onBlur: true)
                                ->minLength(8)
                                ->dehydrated(fn ($state) => filled($state))
                                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                ->helperText('Mínimo 8 caracteres.'),
                            Toggle::make('movimientoActual')
                                ->label(fn (?bool $state): string => $state ? 'Alta' : 'Baja')
                                ->onColor('success')
                                ->offColor('danger')
                                ->onIcon(Heroicon::Bolt)
                                ->offIcon(Heroicon::BoltSlash)
                                ->default(false)
                                ->live()
                                ->dehydrated(false)
                                ->hidden(fn ($livewire): bool => $livewire instanceof CreateRecord)
                                ->helperText('Da de alta o baja al usuario; se creará un movimiento en el sistema.'),
                        ]),

                    ]),
            ]);
    }

    protected static function syncName(Get $get, Set $set): void
    {
        $set('name', static::fullName($get('nombres'), $get('apellidos')));
    }

    protected static function fullName(?string $nombres, ?string $apellidos): string
    {
        return trim(implode(' ', array_filter(
            [$nombres, $apellidos],
            fn (?string $value): bool => filled($value),
        )));
    }
}
