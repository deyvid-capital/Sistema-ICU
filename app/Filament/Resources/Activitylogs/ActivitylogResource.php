<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activitylogs;

use App\Filament\Resources\Activitylogs\Pages\ListActivitylogs;
use App\Filament\Resources\Activitylogs\Pages\ViewActivitylog;
use App\Models\User;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
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

    public static function getLogNameFilterComponent(): SelectFilter
    {
        return parent::getLogNameFilterComponent()
            ->label(__('activitylog::tables.columns.log_name.label'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivitylogs::route('/'),
            'view' => ViewActivitylog::route('/{record}'),
        ];
    }
}
