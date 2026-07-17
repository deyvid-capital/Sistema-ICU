<?php

namespace App\Filament\Resources\AsignacionEquipos\Schemas;

use App\Enums\EstadoEquipo;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesUsuarioRelationManager;
use App\Filament\Resources\Users\RelationManagers\AsignacionesEquipoRelationManager;
use App\Models\AsignacionEquipo;
use App\Models\Equipo;
use App\Models\User;
use App\Rules\EquipoDisponibleParaAsignacion;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
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
                    ->description('Asigna un equipo disponible (sin asignar y sin asignación activa) a un usuario.')
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
                                    ->rule(new NotTrashed(User::class))
                                    ->hiddenOn(AsignacionesEquipoRelationManager::class),
                                Select::make('service_tag')
                                    ->label('Equipo')
                                    ->relationship(
                                        name: 'equipo',
                                        titleAttribute: 'service_tag',
                                        modifyQueryUsing: fn ($query) => $query->with('asignacionActiva'),
                                    )
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
                                    ->helperText('Solo equipos en estado «Sin asignar» y sin asignación abierta pueden asignarse.')
                                    ->rule(new NotTrashed(Equipo::class))
                                    ->rules([
                                        fn (?AsignacionEquipo $record): EquipoDisponibleParaAsignacion => new EquipoDisponibleParaAsignacion(
                                            ignoreAsignacionId: $record?->getKey(),
                                        ),
                                    ])
                                    ->hiddenOn(AsignacionesUsuarioRelationManager::class),
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha de inicio')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de fin')
                                    ->afterOrEqual('fecha_inicio')
                                    ->helperText('Deje vacío mientras la asignación esté activa. Al cerrarla, el equipo podrá volver a asignarse.'),
                                TextInput::make('motivo')
                                    ->label('Motivo')
                                    ->columnSpanFull(),
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
