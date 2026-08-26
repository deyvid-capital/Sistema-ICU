<?php

namespace App\Observers;

use App\Models\MediaSetting;
use App\Services\Archivos\ArchivosService;

class MediaSettingObserver
{
    public function saved(MediaSetting $mediaSetting): void
    {
        $this->olvidar();
    }

    public function deleted(MediaSetting $mediaSetting): void
    {
        $this->olvidar();
    }

    private function olvidar(): void
    {
        app(ArchivosService::class)->olvidar();
    }
}
