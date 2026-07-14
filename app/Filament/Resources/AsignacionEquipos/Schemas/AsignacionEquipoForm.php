<?php

namespace App\Filament\Resources\AsignacionEquipos\Schemas;

use App\Models\Equipo;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class AsignacionEquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de equipo')
                    ->description('Asigna un equipo a un usuario con fechas y motivo de la asignación.')
                    ->icon(Heroicon::OutlinedLink)
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
                                    ->rule(new NotTrashed(User::class)),
                                Select::make('service_tag')
                                    ->label('Equipo')
                                    ->relationship('equipo', 'service_tag')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Equipo $record): string => trim("{$record->service_tag} — {$record->nombre_equipo}")
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(Equipo::class)),
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha de inicio')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de fin')
                                    ->afterOrEqual('fecha_inicio'),
                                TextInput::make('motivo')
                                    ->label('Motivo')
                                    ->columnSpanFull(),
                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
