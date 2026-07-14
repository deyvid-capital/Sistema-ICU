<?php

namespace App\Filament\Resources\MantenimientoEquipos\Schemas;

use App\Models\Equipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class MantenimientoEquipoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mantenimiento de equipo')
                    ->description('Registra el soporte y las capacidades antes y después del mantenimiento.')
                    ->icon(Heroicon::OutlinedWrenchScrewdriver)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
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
                                DatePicker::make('fecha_soporte')
                                    ->label('Fecha de soporte')
                                    ->default(now())
                                    ->required(),
                                TextInput::make('cap_antes')
                                    ->label('Capacidad antes')
                                    ->maxLength(500),
                                TextInput::make('cap_despues')
                                    ->label('Capacidad después')
                                    ->maxLength(500),
                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
