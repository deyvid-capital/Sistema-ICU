<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activitylogs;

use App\Enums\DominioActividad;
use App\Enums\EventoSeguridad;
use App\Enums\ResultadoSeguridad;
use App\Filament\Resources\Activitylogs\Pages\ListActivitylogs;
use App\Filament\Resources\Activitylogs\Pages\ViewActivitylog;
use App\Models\User;
use App\Support\Auditoria\EtiquetasActividad;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rmsramos\Activitylog\Resources\Activitylog\ActivitylogResource as BaseActivitylogResource;

/**
 * Tabla única de auditoría: reúne los cambios de datos que registra el trait
 * LogsIcuActivity y los eventos de acceso que escribe App\Support\Auditoria.
 */
class ActivitylogResource extends BaseActivitylogResource
{
    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->columns([
                static::getLogNameColumnComponent(),
                static::getEventColumnComponent(),
                static::getSubjectTypeColumnComponent(),
                static::getCauserNameColumnComponent(),
                static::getPropertiesColumnComponent(),
                static::getCreatedAtColumnComponent(),
            ])
            ->filters([
                static::getDateFilterComponent(),
                static::getEventFilterComponent(),
                static::getResultadoFilterComponent(),
                static::getLogNameFilterComponent(),
            ]);
    }

    /**
     * Usuario, correo e IP en una sola columna, cada uno en su propia línea:
     * usuario arriba, correo debajo, IP debajo de eso. Si al evento le falta
     * alguno de los tres (un cambio de datos no tiene IP; un correo desconocido
     * no tiene usuario) esa línea se omite en vez de quedar vacía. Sin ninguno
     * de los tres se muestra el placeholder.
     *
     * La búsqueda cubre igualmente los tres. La del nombre de usuario no puede
     * usar el `whereRelation()` por defecto del paquete: en PostgreSQL compara
     * `activity_log.causer_id` (varchar) con `users.id` (uuid) y revienta la
     * consulta, así que se resuelven los IDs del usuario como texto aparte.
     */
    public static function getCauserNameColumnComponent(): Column
    {
        return TextColumn::make('causer.name')
            ->label(__('activitylog::tables.columns.causer.label'))
            ->listWithLineBreaks()
            ->state(fn (Model $record): array => array_values(array_filter([
                $record->causer?->name,
                static::propiedad($record, 'email'),
                static::propiedad($record, 'ip'),
            ], fn (?string $valor): bool => filled($valor))))
            ->placeholder('-')
            ->searchable(query: function (Builder $query, string $search): Builder {
                $causerIds = User::withTrashed()
                    ->whereLike('name', "%{$search}%", caseSensitive: false)
                    ->pluck('id')
                    ->map(fn (mixed $id): string => (string) $id)
                    ->all();

                return $query->where(function (Builder $query) use ($search, $causerIds): void {
                    $query
                        ->where(fn (Builder $query): Builder => $query
                            ->where('causer_type', User::class)
                            ->whereIn('causer_id', $causerIds))
                        ->orWhereRaw("properties->>'email' ILIKE ?", ["%{$search}%"])
                        ->orWhereRaw("properties->>'ip' ILIKE ?", ["%{$search}%"]);
                });
            });
    }

    public static function getLogNameColumnComponent(): Column
    {
        return parent::getLogNameColumnComponent()
            ->formatStateUsing(fn (?string $state): string => EtiquetasActividad::dominio($state))
            ->color(fn (?string $state): string => DominioActividad::tryFrom((string) $state)?->getColor() ?? 'gray');
    }

    public static function getEventColumnComponent(): Column
    {
        return parent::getEventColumnComponent()
            ->formatStateUsing(fn (?string $state): string => EtiquetasActividad::evento($state))
            ->color(fn (?string $state): string => EtiquetasActividad::colorDeEvento($state))
            ->wrap();
    }

    /**
     * El filtro del paquete construye las opciones con los `event` presentes sin
     * descartar los nulos, de modo que la clave vacía mostraba el literal
     * "activitylog::action.event.". Aquí se usa el vocabulario cerrado.
     */
    public static function getEventFilterComponent(): SelectFilter
    {
        return SelectFilter::make('event')
            ->label(__('activitylog::tables.filters.event.label'))
            ->multiple()
            ->options([
                'Cambios en datos' => static::opcionesDeEventosDeModelo(),
                'Seguridad y accesos' => EventoSeguridad::opciones(),
            ]);
    }

    /**
     * Agrupa los eventos de acceso por desenlace, para responder de un vistazo
     * "qué entró bien", "qué falló" y "qué quedó bloqueado".
     */
    public static function getResultadoFilterComponent(): SelectFilter
    {
        return SelectFilter::make('resultado')
            ->label('Resultado del acceso')
            ->options(ResultadoSeguridad::opciones())
            ->query(fn (Builder $query, array $data): Builder => $query->when(
                filled($data['value'] ?? null),
                fn (Builder $query): Builder => $query->whereIn(
                    'event',
                    EventoSeguridad::valoresPorResultado(ResultadoSeguridad::from($data['value'])),
                ),
            ));
    }

    public static function getLogNameFilterComponent(): SelectFilter
    {
        return parent::getLogNameFilterComponent()
            ->label(__('activitylog::tables.columns.log_name.label'))
            ->options(fn (): array => static::getModel()::query()
                ->distinct()
                ->pluck('log_name')
                ->filter()
                ->mapWithKeys(fn (string $logName): array => [$logName => EtiquetasActividad::dominio($logName)])
                ->sort()
                ->all());
    }

    /**
     * @return array<string, string>
     */
    protected static function opcionesDeEventosDeModelo(): array
    {
        $opciones = [];

        foreach (['created', 'updated', 'deleted', 'restored'] as $evento) {
            $opciones[$evento] = EtiquetasActividad::evento($evento);
        }

        return $opciones;
    }

    protected static function propiedad(Model $record, string $clave): ?string
    {
        $valor = $record->properties?->get($clave);

        return filled($valor) ? (string) $valor : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivitylogs::route('/'),
            'view' => ViewActivitylog::route('/{record}'),
        ];
    }
}
