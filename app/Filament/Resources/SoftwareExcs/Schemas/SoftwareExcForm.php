<?php

namespace App\Filament\Resources\SoftwareExcs\Schemas;

use App\Filament\Resources\Software\RelationManagers\ExcepcionesRelationManager;
use App\Filament\Resources\Users\RelationManagers\SoftwareExcepcionesRelationManager;
use App\Models\SoftwareExc;
use App\Models\User;
use App\Rules\NotTrashed;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class SoftwareExcForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Excepción')
                    ->description('Quién recibe el permiso especial, sobre qué software y por cuánto tiempo.')
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('usuario_id')
                            ->label('Usuario')
                            ->relationship('usuario', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->prefixIcon(Heroicon::OutlinedUser)
                            ->placeholder('Selecciona al colaborador')
                            ->helperText('Persona que necesita el software fuera de lo habitual. No puede ser quien aprueba.')
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('aprobador_id', null))
                            ->rule(new NotTrashed(User::class))
                            ->hiddenOn(SoftwareExcepcionesRelationManager::class),
                        Select::make('software_id')
                            ->label('Software')
                            ->relationship('software', 'nombre_software')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->native(false)
                            ->prefixIcon(Heroicon::OutlinedCube)
                            ->placeholder('Selecciona el software')
                            ->helperText('Programa o sistema al que se pide acceso excepcional.')
                            ->hiddenOn(ExcepcionesRelationManager::class),
                        Select::make('tipo_excepcion')
                            ->label('Tipo de excepción')
                            ->options([
                                'Temporal' => 'Temporal',
                                'Permanente' => 'Permanente',
                                'Emergencia' => 'Emergencia',
                            ])
                            ->native(false)
                            ->prefixIcon(Heroicon::OutlinedTag)
                            ->placeholder('Selecciona un tipo')
                            ->helperText('Temporal suele llevar fecha de fin; Emergencia es para un caso urgente.'),
                        DatePicker::make('fecha_inicio')
                            ->label('Fecha de Alta')
                            ->prefixIcon(Heroicon::OutlinedCalendarDays)
                            ->required()
                            ->helperText('Desde cuándo aplica la excepción.'),
                        DatePicker::make('fecha_fin')
                            ->label('Fecha de Baja')
                            ->prefixIcon(Heroicon::OutlinedCalendarDays)
                            ->afterOrEqual('fecha_inicio')
                            ->placeholder('Sin vencimiento')
                            ->helperText('Déjalo vacío si no tiene vencimiento. No puede ser anterior al inicio.'),
                        Textarea::make('motivo')
                            ->label('Motivo')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000)
                            ->placeholder('Explica por qué se pide esta excepción y qué riesgo o necesidad cubre.')
                            ->helperText('Obligatorio. Máximo 1.000 caracteres.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Quién debe aprobar')
                    ->description(fn (?SoftwareExc $record): string => static::aprobacionBloqueada($record)
                        ? 'Esta excepción ya fue decidida. El área y el aprobador no se pueden cambiar.'
                        : 'Elige el área responsable y a una persona de esa área. Mientras esté pendiente puedes cambiarlos (por ejemplo, de Riesgo a Cumplimiento).')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema(static::camposAprobacion()),
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
                ->prefixIcon(Heroicon::OutlinedBuildingOffice2)
                ->placeholder('Selecciona un área')
                ->helperText('Área responsable de firmar esta excepción.')
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
                ->prefixIcon(Heroicon::OutlinedUserCircle)
                ->placeholder('Selecciona primero un área')
                ->helperText('Solo personas de esa área, con acceso al sistema. Recibirán campana y correo.')
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
