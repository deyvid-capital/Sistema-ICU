<?php

namespace App\Filament\Resources\Celulares\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class CelularForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del celular')
                    ->description('Información del equipo celular.')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('id')
                                ->label('ID')
                                ->required()
                                ->maxLength(50)
                                ->unique(
                                    table: 'celulares',
                                    column: 'id',
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
                            TextInput::make('imei')
                                ->label('IMEI')
                                ->maxLength(20)
                                ->unique(
                                    table: 'celulares',
                                    column: 'imei',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                ),
                            DatePicker::make('fecha_compra')
                                ->label('Fecha de compra'),
                            TextInput::make('precio')
                                ->label('Precio')
                                ->numeric(),
                            TextInput::make('procesador')
                                ->label('Procesador')
                                ->maxLength(100),
                            TextInput::make('disco')
                                ->label('Disco')
                                ->maxLength(50),
                            TextInput::make('ram')
                                ->label('RAM')
                                ->maxLength(50),
                            TextInput::make('bateria')
                                ->label('Batería')
                                ->maxLength(50),
                            TextInput::make('color')
                                ->label('Color')
                                ->maxLength(50),
                        ]),
                    ]),
            ]);
    }
}
