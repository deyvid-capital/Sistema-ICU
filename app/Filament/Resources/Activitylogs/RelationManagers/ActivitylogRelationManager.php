<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activitylogs\RelationManagers;

use App\Filament\Resources\Activitylogs\ActivitylogResource;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Rmsramos\Activitylog\ActivitylogPlugin;
use Rmsramos\Activitylog\RelationManagers\ActivitylogRelationManager as BaseActivitylogRelationManager;

class ActivitylogRelationManager extends BaseActivitylogRelationManager
{
    protected static string $relationship = 'activitiesAsSubject';

    public function form(Schema $schema): Schema
    {
        return ActivitylogResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ActivitylogResource::table(
            $table
                ->heading(ActivitylogPlugin::get()->getPluralLabel())
                ->recordActions([
                    ViewAction::make(),
                ])
        );
    }
}
