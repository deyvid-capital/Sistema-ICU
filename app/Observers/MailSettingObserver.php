<?php

namespace App\Observers;

use App\Models\MailSetting;
use App\Services\Configuracion\MailConfigService;

class MailSettingObserver
{
    public function saved(MailSetting $mailSetting): void
    {
        $this->refrescar();
    }

    public function deleted(MailSetting $mailSetting): void
    {
        $this->refrescar();
    }

    private function refrescar(): void
    {
        $servicio = app(MailConfigService::class);
        $servicio->olvidar();
        $servicio->aplicar();
    }
}
