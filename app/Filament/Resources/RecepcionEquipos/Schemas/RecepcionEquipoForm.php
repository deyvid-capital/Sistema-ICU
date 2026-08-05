<?php

namespace App\Filament\Resources\RecepcionEquipos\Schemas;

use App\Filament\Resources\Concerns\ConfiguresFormularioEquipoNumeroField;
use App\Models\AsignacionEquipo;
use App\Models\RecepcionEquipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class RecepcionEquipoForm
{
    use ConfiguresFormularioEquipoNumeroField;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Formulario de recepción')
                    ->description('Registra la recepción de un equipo asociada a una asignación.')
                    ->icon(Heroicon::OutlinedArrowDownTray)
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
                                        table: 'recepcion_equipos',
                                        column: 'asignacion_id',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    )
                                    ->columnSpanFull(),
                                static::formularioEquipoNumeroField(RecepcionEquipo::class, 'recepcion_equipos'),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(
                                        static::syncFormularioEquipoNumeroOnFechaChange(RecepcionEquipo::class),
                                    ),
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
                            ]),
                    ]),
            ]);
    }
}
