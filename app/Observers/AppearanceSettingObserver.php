<?php

namespace App\Observers;

use App\Models\AppearanceSetting;
use App\Services\Configuracion\TemaService;

class AppearanceSettingObserver
{
    public function saved(AppearanceSetting $appearanceSetting): void
    {
        $this->olvidar();
    }

    public function deleted(AppearanceSetting $appearanceSetting): void
    {
        $this->olvidar();
    }

    /**
     * Limpia la caché y el memo del servicio: en procesos largos (colas, tests)
     * el singleton conserva la resolución anterior.
     */
    private function olvidar(): void
    {
        app(TemaService::class)->olvidar();
    }
}
