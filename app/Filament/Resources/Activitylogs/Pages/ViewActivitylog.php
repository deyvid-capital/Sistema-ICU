<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activitylogs\Pages;

use App\Filament\Resources\Activitylogs\ActivitylogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivitylog extends ViewRecord
{
    public static function getResource(): string
    {
        return ActivitylogResource::class;
    }
}
