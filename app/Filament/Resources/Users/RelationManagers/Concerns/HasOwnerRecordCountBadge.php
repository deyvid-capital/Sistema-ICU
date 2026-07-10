<?php

namespace App\Filament\Resources\Users\RelationManagers\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasOwnerRecordCountBadge
{
    protected static bool $isBadgeDeferred = true;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->{static::getRelationshipName()}()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'primary';
    }
}
