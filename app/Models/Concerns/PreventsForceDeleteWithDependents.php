<?php

namespace App\Models\Concerns;

use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Consuming models must declare:
 * protected static array $forceDeleteGuardedRelations = ['users', 'cargos'];
 */
trait PreventsForceDeleteWithDependents
{
    protected static function bootPreventsForceDeleteWithDependents(): void
    {
        // Model::forceDelete() only fires a "forceDeleting" event when the
        // model uses SoftDeletes (SoftDeletes::forceDelete() fires it
        // explicitly). Without that trait, Model::forceDelete() is just an
        // alias for delete() and fires no such event, so plain delete() IS
        // the permanent delete for these models — guard "deleting" instead.
        //
        // Model::__callStatic() also has no built-in magic registrar for
        // "forceDeleting" (unlike creating/deleting/etc.), so calling
        // static::forceDeleting() would fall through to instantiating a new
        // model instance instead of registering a listener. Register the
        // listener explicitly instead.
        $event = in_array(SoftDeletes::class, class_uses_recursive(static::class), true)
            ? 'forceDeleting'
            : 'deleting';

        static::registerModelEvent($event, function (self $model): void {
            $model->assertNoDependentsBeforeForceDelete();
        });
    }

    protected function assertNoDependentsBeforeForceDelete(): void
    {
        foreach (static::$forceDeleteGuardedRelations as $relation) {
            $query = $this->{$relation}();

            if (in_array(SoftDeletes::class, class_uses_recursive($query->getRelated()), true)) {
                $query->withTrashed();
            }

            if (! $query->exists()) {
                continue;
            }

            Notification::make()
                ->title('No se puede eliminar permanentemente')
                ->body("Todavía existen registros relacionados en \"{$relation}\". Elimínalos o reasígnalos primero.")
                ->danger()
                ->send();

            throw new Halt;
        }
    }
}
