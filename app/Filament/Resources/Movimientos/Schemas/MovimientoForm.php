<?php

namespace App\Filament\Resources\Movimientos\Schemas;

use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MovimientoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del movimiento')
                    ->description('Aquí puedes agregar información del movimiento')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('usuario_id')
                            ->label('Usuario')
                            ->relationship('usuario', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->rule(new NotTrashed(User::class)),
                        Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'Alta' => 'Alta',
                                'Baja' => 'Baja',
                            ])
                            ->native(false)
                            ->required()
                            ->live(),
                        DatePicker::make('fecha')
                            ->label('Fecha')
                            ->required()
                            ->default(now()->startOfDay())
                            ->live(),
                        Select::make('cargo_id')
                            ->label('Cargo')
                            ->relationship('cargo', 'cargo')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->aside(),
            ]);
    }
}
