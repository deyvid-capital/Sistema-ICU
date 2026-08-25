<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activitylogs\Pages;

use App\Filament\Resources\Activitylogs\ActivitylogResource;
use Filament\Resources\Pages\ListRecords;

class ListActivitylogs extends ListRecords
{
    protected static string $resource = ActivitylogResource::class;
}
