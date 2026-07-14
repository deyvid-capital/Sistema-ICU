<?php

namespace App\Filament\Resources\AsignacionCargadors\Schemas;

use App\Models\Cargador;
use App\Models\Equipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;

class AsignacionCargadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de cargador')
                    ->description('Asocia un cargador a un equipo con el período de la asignación.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('serial_cargador')
                                    ->label('Cargador')
                                    ->relationship('cargador', 'serial')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(Cargador::class))
                                    ->unique(
                                        table: 'asignacion_cargadores',
                                        column: 'serial_cargador',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    )
                                    ->validationMessages([
                                        'unique' => 'Este cargador ya tiene una asignación activa.',
                                    ]),
                                Select::make('service_tag')
                                    ->label('Equipo')
                                    ->relationship('equipo', 'service_tag')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Equipo $record): string => trim("{$record->service_tag} — {$record->nombre_equipo}")
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(Equipo::class))
                                    ->unique(
                                        table: 'asignacion_cargadores',
                                        column: 'service_tag',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    )
                                    ->validationMessages([
                                        'unique' => 'Este equipo ya tiene un cargador asignado.',
                                    ]),
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha de inicio')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de fin')
                                    ->afterOrEqual('fecha_inicio'),
                            ]),
                    ]),
            ]);
    }
}
