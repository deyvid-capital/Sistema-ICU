<?php

namespace App\Filament\Resources\EntregaEquipos\Schemas;

use App\Models\AsignacionEquipo;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class EntregaEquipoForm
{
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
                                        table: 'entrega_equipos',
                                        column: 'asignacion_id',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    )
                                    ->columnSpanFull(),
                                TextInput::make('numero')
                                    ->label('Número')
                                    ->maxLength(20)
                                    ->unique(
                                        table: 'entrega_equipos',
                                        column: 'numero',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    ),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required(),
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
                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Textarea::make('nota')
                                    ->label('Nota')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
