<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use Rmsramos\Activitylog\Actions\ActivityLogTimelineTableAction;

final class ActivityTimelineAction
{
    public static function make(): ActivityLogTimelineTableAction
    {
        return ActivityLogTimelineTableAction::make('Actividades')
            ->timelineIcons([
                'created' => 'heroicon-m-plus-circle',
                'updated' => 'heroicon-m-pencil-square',
                'deleted' => 'heroicon-m-trash',
                'restored' => 'heroicon-m-arrow-uturn-left',
            ])
            ->timelineIconColors([
                'created' => 'success',
                'updated' => 'warning',
                'deleted' => 'danger',
                'restored' => 'info',
            ])
            ->limit(20);
    }
}
