<?php

namespace App\Filament\Resources\AsignacionCargadors\Schemas;

use App\Enums\EstadoCargador;
use App\Filament\Resources\Cargadors\RelationManagers\AsignacionesRelationManager as CargadorAsignacionesRelationManager;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesCargadorRelationManager;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class AsignacionCargadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de cargador')
                    ->description('Al crear solo se listan cargadores disponibles. Para prestar o ceder uno de otro equipo use la acción del listado.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('service_tag')
                                    ->label('Equipo')
                                    ->relationship('equipo', 'service_tag')
                                    ->options(
                                        Equipo::query()
                                            ->whereDoesntHave('asignacionCargador')
                                            ->get()
                                            ->pluck('service_tag', 'service_tag')
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(Equipo::class))
                                    ->disabledOn('edit')
                                    ->dehydrated(fn (?AsignacionCargador $record): bool => $record === null)
                                    ->hiddenOn(AsignacionesCargadorRelationManager::class),
                                Select::make('serial_cargador')
                                    ->label('Cargador')
                                    ->relationship(
                                        name: 'cargador',
                                        titleAttribute: 'serial',
                                        modifyQueryUsing: function (Builder $query, ?AsignacionCargador $record): Builder {
                                            if ($record === null) {
                                                $query->where('estado', EstadoCargador::Disponible);
                                            }

                                            return $query;
                                        },
                                    )
                                    ->getOptionLabelFromRecordUsing(function (Cargador $record): string {
                                        $estado = $record->estado instanceof EstadoCargador
                                            ? $record->estado->getLabel()
                                            : (string) ($record->estado ?? 'Sin estado');

                                        return trim("{$record->serial} — {$record->marca} {$record->modelo} ({$estado})");
                                    })
                                    ->searchable(['serial', 'marca', 'modelo'])
                                    ->preload()
                                    ->required()
                                    ->helperText(fn (?AsignacionCargador $record): ?string => $record === null
                                        ? 'Solo se listan cargadores en estado «Disponible».'
                                        : null)
                                    ->rule(new NotTrashed(Cargador::class))
                                    ->disabledOn('edit')
                                    ->dehydrated(fn (?AsignacionCargador $record): bool => $record === null)
                                    ->hiddenOn(CargadorAsignacionesRelationManager::class),
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha de Alta')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de Baja')
                                    ->afterOrEqual('fecha_inicio')
                                    ->helperText('Deje vacío mientras la asignación esté activa.'),
                                RichEditor::make('observaciones')
                                    ->label('Observaciones')
                                    ->nullable()
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'underline', 'strike', 'link'],
                                        ['h2', 'h3'],
                                        ['bulletList', 'orderedList'],
                                        ['undo', 'redo'],
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
