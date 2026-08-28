<?php

namespace App\Filament\Resources\AsignacionCargadors\Schemas;

use App\Enums\EstadoCargador;
use App\Enums\EstadoEquipo;
use App\Filament\Resources\AsignacionCargadors\AsignacionCargadorWorkflow;
use App\Filament\Resources\Cargadors\RelationManagers\AsignacionesRelationManager as CargadorAsignacionesRelationManager;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesCargadorRelationManager;
use App\Models\AsignacionCargador;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Component;

class AsignacionCargadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de cargador')
                    ->description('Seleccione primero el equipo y luego el cargador. Si el cargador ya está en otro equipo, se pedirá confirmación (temporal o reasignación).')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->schema([
                        Hidden::make('modo_asignacion')
                            ->dehydrated()
                            ->default(null),
                        Grid::make(2)
                            ->schema([
                                Select::make('service_tag')
                                    ->label('Equipo')
                                    ->relationship('equipo', 'service_tag')
                                    ->getOptionLabelFromRecordUsing(function (Equipo $record): string {
                                        $estado = $record->estado instanceof EstadoEquipo
                                            ? $record->estado->getLabel()
                                            : (string) $record->estado;

                                        return trim("{$record->service_tag} — {$record->nombre_equipo} ({$estado})");
                                    })
                                    ->searchable(['service_tag', 'nombre_equipo'])
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('serial_cargador', null);
                                        $set('modo_asignacion', null);
                                    })
                                    ->rule(new NotTrashed(Equipo::class))
                                    ->hiddenOn(AsignacionesCargadorRelationManager::class),
                                Select::make('serial_cargador')
                                    ->label('Cargador')
                                    ->relationship('cargador', 'serial')
                                    ->getOptionLabelFromRecordUsing(function (Cargador $record): string {
                                        $estado = $record->estado instanceof EstadoCargador
                                            ? $record->estado->getLabel()
                                            : (string) ($record->estado ?? 'Sin estado');

                                        return trim("{$record->serial} — {$record->marca} {$record->modelo} ({$estado})");
                                    })
                                    ->searchable(['serial', 'marca', 'modelo'])
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->disabled(function (Get $get, Component $livewire): bool {
                                        // En el RM del equipo, el service_tag lo aporta el owner.
                                        if ($livewire instanceof AsignacionesCargadorRelationManager) {
                                            return false;
                                        }

                                        return blank($get('service_tag'));
                                    })
                                    ->helperText(function (Get $get, Component $livewire): string {
                                        if ($livewire instanceof AsignacionesCargadorRelationManager) {
                                            return 'Si el cargador ya está en otro equipo, deberá confirmar el movimiento.';
                                        }

                                        return blank($get('service_tag'))
                                            ? 'Seleccione primero el equipo.'
                                            : 'Si el cargador ya está asignado a otro equipo, deberá confirmar el movimiento.';
                                    })
                                    ->rule(new NotTrashed(Cargador::class))
                                    ->afterStateUpdated(function (
                                        ?string $state,
                                        ?string $old,
                                        Set $set,
                                        Get $get,
                                        Component $livewire,
                                        ?AsignacionCargador $record,
                                    ): void {
                                        $set('modo_asignacion', null);

                                        if (blank($state)) {
                                            return;
                                        }

                                        $destino = $get('service_tag');

                                        if (blank($destino) && $livewire instanceof AsignacionesCargadorRelationManager) {
                                            $destino = $livewire->getOwnerRecord()->getKey();
                                        }

                                        if (blank($destino)) {
                                            return;
                                        }

                                        $activa = AsignacionCargadorWorkflow::asignacionActivaDelCargador(
                                            $state,
                                            $record?->getKey(),
                                        );

                                        // Solo pide confirmación si está activo en OTRO equipo.
                                        if ($activa === null || $activa->service_tag === $destino) {
                                            return;
                                        }

                                        $set('serial_cargador', $old);
                                        $livewire->mountAction('confirmarMovimientoCargador', [
                                            'serial' => $state,
                                        ]);
                                    })
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
