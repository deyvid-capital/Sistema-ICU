<?php

namespace App\Filament\Resources\AsignacionEquipos\Schemas;

use App\Enums\EstadoEquipo;
use App\Filament\Resources\Concerns\ConfiguresFormularioEquipoNumeroField;
use App\Filament\Resources\Equipos\RelationManagers\AsignacionesUsuarioRelationManager;
use App\Filament\Resources\Users\RelationManagers\AsignacionesEquipoRelationManager;
use App\Models\AsignacionEquipo;
use App\Models\EntregaEquipo;
use App\Models\Equipo;
use App\Models\User;
use App\Rules\EquipoDisponibleParaAsignacion;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class AsignacionEquipoForm
{
    use ConfiguresFormularioEquipoNumeroField;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Asignación de equipo')
                    ->columnSpanFull()
                    ->persistTab()
                    ->id('asignacion-equipo-form-tabs')
                    ->scrollable(false)
                    ->tabs([
                        Tab::make('Asignación')
                            ->icon(Heroicon::OutlinedLink)
                            ->columns(2)
                            ->schema([
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(User::class))
                                    ->disabledOn('edit')
                                    ->dehydrated(fn (?AsignacionEquipo $record): bool => $record === null)
                                    ->hiddenOn(AsignacionesEquipoRelationManager::class),
                                Select::make('service_tag')
                                    ->label('Equipo')
                                    ->relationship(
                                        name: 'equipo',
                                        titleAttribute: 'service_tag',
                                        modifyQueryUsing: function (Builder $query, ?AsignacionEquipo $record): Builder {
                                            if ($record === null) {
                                                $query->where('estado', EstadoEquipo::SinAsignar);
                                            }

                                            return $query;
                                        },
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
                                    ->helperText(fn (?AsignacionEquipo $record): ?string => $record === null
                                        ? 'Solo se listan equipos en estado «Sin asignar».'
                                        : null)
                                    ->rule(new NotTrashed(Equipo::class))
                                    ->rules(fn (?AsignacionEquipo $record): array => $record === null
                                        ? [new EquipoDisponibleParaAsignacion]
                                        : [])
                                    ->disabledOn('edit')
                                    ->dehydrated(fn (?AsignacionEquipo $record): bool => $record === null)
                                    ->hiddenOn(AsignacionesUsuarioRelationManager::class),
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha de Alta')
                                    ->default(now())
                                    ->required(),
                                DatePicker::make('fecha_fin')
                                    ->label('Fecha de Baja')
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
                        Tab::make('Form. Entrega')
                            ->icon(Heroicon::OutlinedArrowUpTray)
                            ->badgeColor('gray')
                            ->schema([
                                Group::make()
                                    ->relationship(
                                        'entrega',
                                        condition: fn (?array $state): bool => filled($state['fecha'] ?? null),
                                    )
                                    ->schema([
                                        Grid::make(5)
                                            ->schema([
                                                static::formularioEquipoNumeroField(EntregaEquipo::class, 'entregas_equipos'),
                                                DatePicker::make('fecha')
                                                    ->label('Fecha')
                                                    ->default(now())
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(
                                                        static::syncFormularioEquipoNumeroOnFechaChange(EntregaEquipo::class),
                                                    ),
                                                Toggle::make('mouse')
                                                    ->label('Mouse')
                                                    ->onColor('success')
                                                    ->offColor('danger')
                                                    ->default(false)
                                                    ->inline(false),
                                                Toggle::make('cooler')
                                                    ->label('Cooler')
                                                    ->onColor('success')
                                                    ->offColor('danger')
                                                    ->default(false)
                                                    ->inline(false),
                                                Toggle::make('audifonos')
                                                    ->label('Audífonos')
                                                    ->onColor('success')
                                                    ->offColor('danger')
                                                    ->default(false)
                                                    ->inline(false),
                                                RichEditor::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->columnSpanFull()
                                                    ->toolbarButtons([
                                                        ['bold', 'italic', 'underline', 'strike', 'link'],
                                                        ['h2', 'h3'],
                                                        ['bulletList', 'orderedList'],
                                                        ['undo', 'redo'],
                                                    ])
                                                    ->columnSpanFull(),
                                                RichEditor::make('nota')
                                                    ->label('Nota')
                                                    ->columnSpanFull()
                                                    ->toolbarButtons([
                                                        ['bold', 'italic', 'underline', 'strike', 'link'],
                                                        ['h2', 'h3'],
                                                        ['bulletList', 'orderedList'],
                                                        ['undo', 'redo'],
                                                    ])
                                                    ->default(
                                                        '<p><strong>1. Responsabilidad del Usuario:
                                                        </strong> Cada usuario es responsable de los
                                                        </p><p>equipos y accesorios que se les ha asignado. Esto incluye su uso
                                                        </p><p>adecuado y su mantenimiento en buen estado.
                                                        </p><p><strong>2. Cuidado del Equipo:
                                                        </strong> Se espera que todos los usuarios manejen los
                                                        </p><p>equipos y accesorios con cuidado y diligencia. Eviten exponer los equipos
                                                        </p><p>a condiciones que puedan causar daños, como humedad, calor extremo o
                                                        </p><p>golpes.</p><p><strong>3. Extravío o Deterioro:
                                                        </strong> En caso de extravío o deterioro del equipo
                                                        </p><p>debido a negligencia o mal uso, el usuario será responsable de asumir el
                                                        </p><p>costo de reparación o reemplazo del mismo</p>'
                                                    )
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),
                            ]),

                    ]),
            ]);
    }
}
