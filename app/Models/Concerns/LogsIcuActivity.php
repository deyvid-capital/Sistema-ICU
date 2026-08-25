<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\DominioActividad;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsIcuActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->useLogName($this->activityLogName())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->dontLogIfAttributesChangedOnly(['updated_at']);

        if ($this->getFillable() !== []) {
            $options->logFillable();
        } else {
            $options->logAll();
        }

        $except = $this->activityLogExcept();

        if ($except !== []) {
            $options->logExcept($except);
        }

        return $options;
    }

    /**
     * Dominio funcional que se guarda en `activity_log.log_name` y alimenta el
     * filtro "Tipo" del panel. Los modelos pueden sobrescribirlo si necesitan
     * un dominio distinto al que resuelve el mapa central.
     */
    protected function activityLogName(): string
    {
        return DominioActividad::paraModelo($this)?->value
            ?? (string) config('activitylog.default_log_name');
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcept(): array
    {
        return [
            'password',
            'remember_token',
        ];
    }
}
