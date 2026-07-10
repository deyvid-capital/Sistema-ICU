<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

abstract class ManageUserRelations extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @return array<class-string>
     */
    abstract protected function relationManagers(): array;

    /**
     * @return array<string>
     */
    abstract protected static function countedRelationships(): array;

    protected function getAllRelationManagers(): array
    {
        return $this->relationManagers();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getRelationManagersContentComponent(),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        if (filled(static::$title)) {
            return static::$title;
        }

        return static::getNavigationLabel().' — '.$this->getRecordTitle();
    }

    /**
     * @param  array<string, mixed>  $urlParameters
     * @return array<NavigationItem>
     */
    public static function getNavigationItems(array $urlParameters = []): array
    {
        $items = parent::getNavigationItems($urlParameters);

        $record = $urlParameters['record'] ?? null;

        if (! $record instanceof User) {
            return $items;
        }

        $count = static::relationshipCount($record);

        return array_map(
            fn (NavigationItem $item): NavigationItem => $item->badge(
                $count > 0 ? (string) $count : null,
                color: $count > 0 ? 'primary' : null,
            ),
            $items,
        );
    }

    protected static function relationshipCount(User $record): int
    {
        return array_sum(array_map(
            fn (string $relationship): int => $record->{$relationship}()->count(),
            static::countedRelationships(),
        ));
    }
}
