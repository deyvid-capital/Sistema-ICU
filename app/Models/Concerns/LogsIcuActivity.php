<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

trait LogsIcuActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
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
