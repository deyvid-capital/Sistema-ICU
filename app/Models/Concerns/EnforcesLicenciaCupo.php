<?php

namespace App\Models\Concerns;

use App\Models\Licencia;
use App\Services\Licencias\GestionarCupoLicencia;

/**
 * Consuming models must belong to a licencia and expose `estado`.
 */
trait EnforcesLicenciaCupo
{
    protected static function bootEnforcesLicenciaCupo(): void
    {
        static::creating(function (self $model): void {
            $model->assertCupoDisponibleSiConsume();
        });

        static::updating(function (self $model): void {
            $model->assertCupoDisponibleSiConsume();
        });

        static::restoring(function (self $model): void {
            $model->assertCupoDisponibleSiConsume(forzar: true);
        });

        static::created(function (self $model): void {
            $model->notificarCupoAgotadoSiCorresponde();
        });

        static::updated(function (self $model): void {
            if (! $model->wasChanged(['estado', 'licencia_id'])) {
                return;
            }

            $model->notificarCupoAgotadoSiCorresponde();
        });

        static::restored(function (self $model): void {
            $model->notificarCupoAgotadoSiCorresponde();
        });
    }

    protected function assertCupoDisponibleSiConsume(bool $forzar = false): void
    {
        if (! $this->vaAConsumirAsiento($forzar)) {
            return;
        }

        $licencia = $this->licenciaParaCupo();

        if ($licencia === null) {
            return;
        }

        app(GestionarCupoLicencia::class)->haltarSiNoPuedeConsumir(
            $licencia,
            $this->exists ? $this : null,
        );
    }

    protected function notificarCupoAgotadoSiCorresponde(): void
    {
        if (! $this->estado) {
            return;
        }

        $licencia = $this->licenciaParaCupo();

        if ($licencia === null) {
            return;
        }

        app(GestionarCupoLicencia::class)->notificarSiSeAgoto($licencia);
    }

    protected function vaAConsumirAsiento(bool $forzar = false): bool
    {
        if ($forzar) {
            return true;
        }

        if (! $this->estado) {
            return false;
        }

        if (! $this->exists) {
            return true;
        }

        return $this->isDirty('estado') || $this->isDirty('licencia_id');
    }

    protected function licenciaParaCupo(): ?Licencia
    {
        if ($this->relationLoaded('licencia') && $this->licencia instanceof Licencia) {
            return $this->licencia;
        }

        if (blank($this->licencia_id)) {
            return null;
        }

        return Licencia::query()->find($this->licencia_id);
    }
}
