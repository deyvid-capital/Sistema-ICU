<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

trait CascadesDependentSoftDeletes
{
    protected static function bootCascadesDependentSoftDeletes(): void
    {
        static::deleting(function (Model $model): void {
            if (! in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
                return;
            }

            if ($model->isForceDeleting()) {
                return;
            }

            $model->cascadeSoftDeleteDependents();
        });

        static::restoring(function (Model $model): void {
            $model->cascadeRestoreDependents();
        });
    }

    /**
     * @return array<string>
     */
    protected function dependentSoftDeleteRelations(): array
    {
        return [];
    }

    protected function cascadeSoftDeleteDependents(): void
    {
        DB::transaction(function (): void {
            foreach ($this->dependentSoftDeleteRelations() as $relation) {
                $this->{$relation}()
                    ->get()
                    ->each
                    ->delete();
            }
        });
    }

    protected function cascadeRestoreDependents(): void
    {
        DB::transaction(function (): void {
            foreach ($this->dependentSoftDeleteRelations() as $relation) {
                $this->{$relation}()
                    ->onlyTrashed()
                    ->get()
                    ->each
                    ->restore();
            }
        });
    }
}
