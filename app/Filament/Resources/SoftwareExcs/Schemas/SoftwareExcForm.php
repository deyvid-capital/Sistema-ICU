<?php

namespace App\Filament\Resources\SoftwareExcs\Schemas;

use App\Filament\Resources\Users\RelationManagers\SoftwareExcepcionesRelationManager;
use App\Models\SoftwareExc;
use App\Models\User;
use App\Rules\NotTrashed;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class SoftwareExcForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('aprobador_id', null))
                    ->rule(new NotTrashed(User::class))
                    ->hiddenOn(SoftwareExcepcionesRelationManager::class),
                Select::make('software_id')
                    ->label('Software')
                    ->relationship('software', 'nombre_software')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('tipo_excepcion')
                    ->label('Tipo de excepcion')
                    ->options([
                        'Temporal' => 'Temporal',
                        'Permanente' => 'Permanente',
                        'Emergencia' => 'Emergencia',
                    ]),
                ...static::camposAprobacion(),
                Textarea::make('motivo')
                    ->label('Motivo')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->required(),
                DatePicker::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->afterOrEqual('fecha_inicio'),
            ]);
    }

    /**
     * @return array<int, Select>
     */
    public static function camposAprobacion(): array
    {
        return [
            Select::make('area_aprobadora_id')
                ->label('Área que aprueba')
                ->relationship('areaAprobadora', 'area')
                ->searchable()
                ->preload()
                ->required()
                ->live()
                ->native(false)
                ->placeholder('Selecciona un área')
                ->helperText('Elige el área responsable de aprobar esta excepción. Puedes cambiarla mientras esté pendiente.')
                ->disabled(fn (?SoftwareExc $record): bool => static::aprobacionBloqueada($record))
                ->afterStateUpdated(fn (Set $set) => $set('aprobador_id', null)),
            Select::make('aprobador_id')
                ->label('Aprobador')
                ->relationship(
                    name: 'aprobador',
                    titleAttribute: 'name',
                    modifyQueryUsing: function (Builder $query, Get $get): Builder {
                        return static::aprobadoresQuery($query, $get('area_aprobadora_id'), $get('usuario_id'));
                    },
                )
                ->searchable()
                ->preload()
                ->required()
                ->native(false)
                ->placeholder('Selecciona primero un área')
                ->helperText('Solo personas de esa área con acceso al sistema. No puede ser el usuario de la excepción.')
                ->disabled(fn (Get $get, ?SoftwareExc $record): bool => blank($get('area_aprobadora_id')) || static::aprobacionBloqueada($record))
                ->rule(function (Get $get): Closure {
                    return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if (blank($value)) {
                            return;
                        }

                        if ((string) $value === (string) $get('usuario_id')) {
                            $fail('El aprobador no puede ser el mismo usuario de la excepción.');

                            return;
                        }

                        $pertenece = User::query()
                            ->whereKey($value)
                            ->where('id_area', $get('area_aprobadora_id'))
                            ->exists();

                        if (! $pertenece) {
                            $fail('El aprobador seleccionado no pertenece al área elegida.');
                        }
                    };
                }),
        ];
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public static function aprobadoresQuery(Builder $query, mixed $areaId, mixed $usuarioId): Builder
    {
        return $query
            ->where('id_area', $areaId)
            ->where('acceso_habilitado', true)
            ->whereHas('roles')
            ->when(filled($usuarioId), fn (Builder $builder): Builder => $builder->whereKeyNot($usuarioId));
    }

    public static function aprobacionBloqueada(?SoftwareExc $record): bool
    {
        return $record instanceof SoftwareExc && ! $record->estaPendiente();
    }
}
