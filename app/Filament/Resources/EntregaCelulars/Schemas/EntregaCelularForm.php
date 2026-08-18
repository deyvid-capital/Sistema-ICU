<?php

namespace App\Filament\Resources\EntregaCelulars\Schemas;

use App\Filament\Resources\Concerns\ConfiguresFormularioEquipoNumeroField;
use App\Models\AsigCelular;
use App\Models\EntregaCelular;
use App\Rules\NotTrashed;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class EntregaCelularForm
{
    use ConfiguresFormularioEquipoNumeroField;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Formulario de entrega')
                    ->description('Registra la entrega de un celular asociada a una asignación activa.')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('asignacion_cel_id')
                                    ->label('Asignación')
                                    ->relationship(
                                        name: 'asignacion',
                                        titleAttribute: 'id',
                                        modifyQueryUsing: fn (Builder $query) => $query->with(['celular', 'usuario']),
                                    )
                                    ->getOptionLabelFromRecordUsing(
                                        fn (AsigCelular $record): string => trim(sprintf(
                                            '%s — %s (%s)',
                                            $record->celular?->id ?? 'sin celular',
                                            $record->usuario?->name ?? 'sin usuario',
                                            $record->fecha_ini?->format('d/m/Y') ?? '—',
                                        ))
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->rule(new NotTrashed(AsigCelular::class))
                                    ->unique(
                                        table: 'entregas_celulares',
                                        column: 'asignacion_cel_id',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                    )
                                    ->columnSpanFull(),
                                static::formularioEquipoNumeroField(EntregaCelular::class, 'entregas_celulares'),
                                DatePicker::make('fecha')
                                    ->label('Fecha')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(
                                        static::syncFormularioEquipoNumeroOnFechaChange(EntregaCelular::class),
                                    ),
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
