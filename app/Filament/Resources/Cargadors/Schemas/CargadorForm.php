<?php

namespace App\Filament\Resources\Cargadors\Schemas;

use App\Enums\EstadoCargador;
use App\Models\Cargador;
use App\Services\CambiarEstadoCargador;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;

class CargadorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del cargador')
                    ->description('Información del cargador de laptop.')
                    ->icon(Heroicon::OutlinedBolt)
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('serial')
                                ->label('Serial')
                                ->required()
                                ->maxLength(50)
                                ->unique(
                                    table: 'cargadores',
                                    column: 'serial',
                                    ignoreRecord: true,
                                    modifyRuleUsing: fn (Unique $rule): Unique => $rule->whereNull('deleted_at'),
                                )
                                ->disabledOn('edit')
                                ->dehydrated(),
                            TextInput::make('marca')
                                ->label('Marca')
                                ->maxLength(100),
                            TextInput::make('modelo')
                                ->label('Modelo')
                                ->maxLength(100),
                            Hidden::make('confirmar_cierre_asignacion')
                                ->dehydrated(false)
                                ->default(false),
                            Select::make('estado')
                                ->label('Estado')
                                ->native(false)
                                ->options(fn (?Cargador $record): array => static::opcionesDeEstado($record))
                                ->default(EstadoCargador::Disponible)
                                ->required()
                                ->live()
                                ->helperText('Asignado, prestado y cedido se actualizan solos. Si cambia el estado de un cargador asignado, se cierra esa asignación con fecha de baja.')
                                ->afterStateUpdated(function (
                                    ?string $state,
                                    ?string $old,
                                    Set $set,
                                    Component $livewire,
                                    ?Cargador $record,
                                ): void {
                                    $set('confirmar_cierre_asignacion', false);

                                    if (blank($state) || ! $record instanceof Cargador) {
                                        return;
                                    }

                                    $nuevo = EstadoCargador::tryFrom($state);

                                    if ($nuevo === null || ! app(CambiarEstadoCargador::class)->requiereConfirmacion($record, $nuevo)) {
                                        return;
                                    }

                                    $set('estado', $old);
                                    $livewire->mountAction('confirmarCierreAsignacionCargador', [
                                        'estado' => $state,
                                    ]);
                                }),
                        ]),
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
            ]);
    }

    /**
     * Los estados de asignación no se ofrecen; se conserva el actual si ya lo tiene.
     *
     * @return array<string, string>
     */
    protected static function opcionesDeEstado(?Cargador $record): array
    {
        $actual = $record?->estado;

        return collect(EstadoCargador::cases())
            ->filter(fn (EstadoCargador $estado): bool => $estado->esSeleccionableManualmente() || $estado === $actual)
            ->mapWithKeys(fn (EstadoCargador $estado): array => [$estado->value => $estado->getLabel()])
            ->all();
    }
}
