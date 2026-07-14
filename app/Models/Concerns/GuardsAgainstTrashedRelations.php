<?php

namespace App\Models\Concerns;

use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

/**
 * Consuming models must declare:
 * protected static array $trashGuardedRelations = ['usuario'];
 */
trait GuardsAgainstTrashedRelations
{
    protected static function bootGuardsAgainstTrashedRelations(): void
    {
        // Only guard creation of new assignments. Updates are intentionally
        // not guarded: cascaded restores (see CascadesDependentSoftDeletes)
        // restore dependents while the owner is still mid-restore, and
        // editing an existing record for an already-trashed owner (e.g. to
        // fix a typo) is a legitimate administrative action.
        static::creating(function (self $model): void {
            $model->assertGuardedRelationsNotTrashed();
        });
    }

    protected function assertGuardedRelationsNotTrashed(): void
    {
        foreach (static::$trashGuardedRelations as $relation) {
            $related = $this->{$relation};

            if (! $related?->trashed()) {
                continue;
            }

            $body = match ($relation) {
                'usuario' => 'El usuario está en la papelera de reciclaje y no se le puede asignar.',
                'equipo' => 'El equipo está en la papelera de reciclaje y no se le puede asignar.',
                'cargador' => 'El cargador está en la papelera de reciclaje y no se le puede asignar.',
                default => "El registro relacionado \"{$relation}\" está en la papelera de reciclaje y no se puede asignar.",
            };

            Notification::make()
                ->title('No se puede asignar')
                ->body($body)
                ->danger()
                ->send();

            throw new Halt;
        }
    }
}
