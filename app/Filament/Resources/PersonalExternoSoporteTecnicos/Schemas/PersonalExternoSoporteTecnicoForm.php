<?php

namespace App\Filament\Resources\PersonalExternoSoporteTecnicos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class PersonalExternoSoporteTecnicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Soporte externo')
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
                                        table: 'personal_externo_soporte_tecnico',
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
