<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activitylogs;

use App\Enums\DominioActividad;
use App\Filament\Resources\Activitylogs\Pages\ListActivitylogs;
use App\Filament\Resources\Activitylogs\Pages\ViewActivitylog;
use App\Models\User;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rmsramos\Activitylog\Resources\Activitylog\ActivitylogResource as BaseActivitylogResource;

class ActivitylogResource extends BaseActivitylogResource
{
    /**
     * La búsqueda por defecto del paquete usa `whereRelation()` sobre la relación
     * polimórfica `causer`, lo que en PostgreSQL compara `activity_log.causer_id`
     * (varchar) con `users.id` (uuid) y revienta la consulta. Se reemplaza por una
     * búsqueda que resuelve los IDs del usuario como texto.
     */
    public static function getCauserNameColumnComponent(): Column
    {
        return parent::getCauserNameColumnComponent()
            ->searchable(query: function (Builder $query, string $search): Builder {
                $causerIds = User::withTrashed()
                    ->whereLike('name', "%{$search}%", caseSensitive: false)
                    ->pluck('id')
                    ->map(fn (mixed $id): string => (string) $id)
                    ->all();

                return $query
                    ->where('causer_type', User::class)
                    ->whereIn('causer_id', $causerIds);
            });
    }

    public static function getLogNameColumnComponent(): Column
    {
        return parent::getLogNameColumnComponent()
            ->formatStateUsing(fn (?string $state): string => static::etiquetaDeDominio($state))
            ->color(fn (?string $state): string => DominioActividad::tryFrom((string) $state)?->getColor() ?? 'gray');
    }

    public static function getLogNameFilterComponent(): SelectFilter
    {
        return parent::getLogNameFilterComponent()
            ->label(__('activitylog::tables.columns.log_name.label'))
            ->options(fn (): array => static::getModel()::query()
                ->distinct()
                ->pluck('log_name')
                ->filter()
                ->mapWithKeys(fn (string $logName): array => [$logName => static::etiquetaDeDominio($logName)])
                ->sort()
                ->all());
    }

    /**
     * Etiqueta legible de un `log_name`. Los valores no mapeados (por ejemplo
     * registros históricos) se muestran tal cual, en formato de título.
     */
    protected static function etiquetaDeDominio(?string $logName): string
    {
        if (blank($logName)) {
            return '-';
        }

        return DominioActividad::tryFrom($logName)?->getLabel()
            ?? Str::headline($logName);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivitylogs::route('/'),
            'view' => ViewActivitylog::route('/{record}'),
        ];
    }
}
