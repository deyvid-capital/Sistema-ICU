<?php

namespace App\Filament\Resources\Cargadors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class CargadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del cargador')
                    ->description('Información del cargador de laptop.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('serial')
                                ->label('Serial')
                                ->required()
                                ->maxLength(50)
                                ->unique(
                                    table: 'cargadores',
                                    column: 'serial',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                )
                                ->disabledOn('edit')
                                ->dehydrated(),
                            TextInput::make('marca')
                                ->label('Marca')
                                ->maxLength(100),
                            TextInput::make('modelo')
                                ->label('Modelo')
                                ->maxLength(100),
                            Select::make('estado')
                                ->label('Estado')
                                ->native(false)
                                ->options([
                                    'disponible' => 'Disponible',
                                    'asignado' => 'Asignado',
                                    'danado' => 'Dañado',
                                    'baja' => 'Baja',
                                ])
                                ->default('disponible'),
                            Textarea::make('observaciones')
                                ->label('Observaciones')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }
}
