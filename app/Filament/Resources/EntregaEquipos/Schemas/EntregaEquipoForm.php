<?php

namespace App\Filament\Resources\EntregaEquipos\Schemas;

use App\Filament\Resources\Concerns\ConfiguresFormularioEquipoNumeroField;
use App\Models\AsignacionEquipo;
use App\Models\EntregaEquipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class EntregaEquipoForm
{
    use ConfiguresFormularioEquipoNumeroField;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Formulario de entrega')
                    ->description('Registra la entrega de un equipo asociada a una asignación activa.')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('asignacion_id')
                                    ->label('Asignación')
                                    ->relationship(
                                        name: 'asignacion',
                                        titleAttribute: 'id',
                                        modifyQueryUsing: fn (Builder $query) => $query->with(['equipo', 'usuario']),
                                    )
                                    ->getOptionLabelFromRecordUsing(
                                        fn (AsignacionEquipo $record): string => trim(sprintf(
                                            '%s — %s (%s)',
                                            $record->service_tag,
                                            $record->usuario?->name ?? 'sin usuario',
                                            $record->fecha_inicio?->format('d/m/Y') ?? '—',
                                        ))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(AsignacionEquipo::class))
                                    ->unique(
                                        table: 'entregas_equipos',
                                        column: 'asignacion_id',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    )
                                    ->columnSpanFull(),
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
                                    ->default(false)
                                    ->inline(false),
                                Toggle::make('cooler')
                                    ->label('Cooler')
                                    ->default(false)
                                    ->inline(false),
                                Toggle::make('audifonos')
                                    ->label('Audífonos')
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
            ]);
    }
}
