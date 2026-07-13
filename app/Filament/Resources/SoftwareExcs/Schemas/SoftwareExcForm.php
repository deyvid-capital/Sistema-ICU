<?php

namespace App\Filament\Resources\SoftwareExcs\Schemas;

use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class SoftwareExcForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rule(new NotTrashed(User::class)),
                Select::make('software_id')
                    ->label('Software')
                    ->relationship('software', 'nombre_software')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('tipo_excepcion')
                    ->label('Tipo de excepcion')
                    ->options([
                        'Temporal' => 'Temporal',
                        'Permanente' => 'Permanente',
                        'Emergencia' => 'Emergencia',
                    ]),
                Textarea::make('motivo')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->required(),
                DatePicker::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->afterOrEqual('fecha_inicio'),
            ]);
    }
}
