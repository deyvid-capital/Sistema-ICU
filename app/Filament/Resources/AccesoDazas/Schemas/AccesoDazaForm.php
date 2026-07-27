<?php

namespace App\Filament\Resources\AccesoDazas\Schemas;

use App\Filament\Resources\Users\RelationManagers\AccesosDazaRelationManager;
use App\Models\AccesoDaza;
use App\Models\Fondo;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class AccesoDazaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del acceso DAZA')
                    ->description('Aquí puedes agregar información del acceso DAZA')
                    ->icon(Heroicon::OutlinedInformationCircle)
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
                                    ->hiddenOn(AccesosDazaRelationManager::class),
                                TextInput::make('usuario_daza')
                                    ->label('Usuario DAZA')
                                    ->maxLength(50),
                                Select::make('nivel_acceso')
                                    ->label('Nivel de acceso')
                                    ->native(false)
                                    ->options([
                                        'Usuario Estándar' => 'Usuario Estándar',
                                        'Usuario Administrador' => 'Usuario Administrador',
                                        'Usuario de Soporte' => 'Usuario de Soporte',
                                        'Usuario de Administración' => 'Usuario de Administración',
                                    ])
                                    ->required(),
                                Select::make('fondo_id')
                                    ->label('Fondo')
                                    ->relationship('fondo', 'nombre_completo')
                                    ->getOptionLabelFromRecordUsing(fn (Fondo $record): string => $record->nombre_completo ?? $record->codigo)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->unique(
                                        table: AccesoDaza::class,
                                        column: 'fondo_id',
                                        ignoreRecord: true,
                                        modifyRuleUsing: function (Unique $rule, Get $get, Component $livewire, ?AccesoDaza $record): Unique {
                                            $usuarioId = $livewire instanceof RelationManager
                                                ? $livewire->getOwnerRecord()->getKey()
                                                : $get('usuario_id');

                                            return $rule
                                                ->where('usuario_id', $usuarioId)
                                                ->where('ambiente', $get('ambiente'))
                                                ->ignore($record);
                                        },
                                    )
                                    ->validationMessages([
                                        'unique' => 'Este usuario ya tiene asignado este fondo en el ambiente seleccionado.',
                                    ]),
                                Select::make('ambiente')
                                    ->label('Ambiente')
                                    ->native(false)
                                    ->live()
                                    ->options([
                                        'Producción' => 'Producción',
                                        'Contingencia' => 'Contingencia',
                                    ])
                                    ->required(),
                                DatePicker::make('fecha')
                                    ->default(now())
                                    ->label('Fecha'),
                                Toggle::make('estado')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }
}
