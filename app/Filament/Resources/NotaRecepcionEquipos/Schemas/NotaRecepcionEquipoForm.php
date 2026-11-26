<?php

namespace App\Filament\Resources\NotaRecepcionEquipos\Schemas;

use App\Filament\Resources\Concerns\ConfiguresFormularioEquipoNumeroField;
use App\Filament\Resources\Equipos\RelationManagers\NotasRecepcionRelationManager as EquipoNotasRecepcionRelationManager;
use App\Filament\Resources\PersonalExternoSoporteTecnicos\RelationManagers\NotasRecepcionRelationManager as PersonalExternoSoporteTecnicoNotasRecepcionRelationManager;
use App\Models\Equipo;
use App\Models\NotaRecepcionEquipo;
use App\Models\PersonalExternoSoporteTecnico;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class NotaRecepcionEquipoForm
{
    use ConfiguresFormularioEquipoNumeroField;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Nota de recepción')
                    ->description('Registra la recepción de un equipo proveniente de un soporte técnico externo.')
                    ->icon(Heroicon::OutlinedArrowLeftEndOnRectangle)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('equipo_id')
                                    ->label('Equipo')
                                    ->relationship('equipo', 'service_tag')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (Equipo $record): string => trim("{$record->service_tag} — {$record->nombre_equipo}")
                                    )
                                    ->searchable(['service_tag', 'nombre_equipo'])
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(Equipo::class))
                                    ->hiddenOn(EquipoNotasRecepcionRelationManager::class),
                                Select::make('personal_externo_soporte_id')
                                    ->label('Soporte externo')
                                    ->relationship('personalExternoSoporte', 'nombre')
                                    ->getOptionLabelFromRecordUsing(
                                        fn (PersonalExternoSoporteTecnico $record): string => trim("{$record->nombre} — {$record->carnet}")
                                    )
                                    ->searchable(['nombre', 'carnet', 'empresa'])
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(PersonalExternoSoporteTecnico::class))
                                    ->hiddenOn(PersonalExternoSoporteTecnicoNotasRecepcionRelationManager::class),
                                static::formularioEquipoNumeroField(NotaRecepcionEquipo::class, 'notas_recepcion_equipos'),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(
                                        static::syncFormularioEquipoNumeroOnFechaChange(NotaRecepcionEquipo::class),
                                    ),
                                Textarea::make('nota')
                                    ->label('Nota')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
