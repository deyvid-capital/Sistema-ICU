<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class MovimientoForm
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
                Select::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'Alta' => 'Alta',
                        'Baja' => 'Baja',
                    ])
                    ->required(),
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->required(),
                Select::make('cargo_id')
                    ->label('Cargo')
                    ->relationship('cargo', 'cargo')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
