<?php

namespace App\Filament\Resources\UsuarioExternoSoporteEquipos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class UsuarioExternoSoporteEquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Usuario externo de soporte técnico')
                    ->description('Personas externas a la organización que reciben o entregan equipos.')
                    ->icon(Heroicon::OutlinedUser)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('carnet')
                                    ->label('Carnet / CI')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(
                                        table: 'usuarios_externos_soporte_equipos',
                                        column: 'carnet',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    ),
                                TextInput::make('empresa')
                                    ->label('Empresa')
                                    ->maxLength(255),
                                TextInput::make('cargo')
                                    ->label('Cargo')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }
}
