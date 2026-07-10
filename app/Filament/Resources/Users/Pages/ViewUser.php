<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $navigationLabel = 'Ver';

    protected static ?int $navigationSort = 1;

    protected function getAllRelationManagers(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar usuario')
                ->visible(fn (User $record): bool => ! $record->trashed()
                                    && $record->id !== auth()->user()->id),

        ];
    }
}
