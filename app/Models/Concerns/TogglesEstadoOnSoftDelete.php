<?php

namespace App\Models\Concerns;

trait TogglesEstadoOnSoftDelete
{
    protected static function bootTogglesEstadoOnSoftDelete(): void
    {
        static::deleting(function (self $model): void {
            if ($model->isForceDeleting()) {
                return;
            }

            $model->estado = false;
            $model->saveQuietly();
        });

        static::restoring(function (self $model): void {
            $model->estado = true;
            $model->saveQuietly();
        });
    }
}
