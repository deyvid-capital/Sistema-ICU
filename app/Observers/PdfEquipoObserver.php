<?php

namespace App\Observers;

use App\Filament\Resources\PdfEquipos\PdfEquipoResource;
use App\Models\PdfEquipo;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;

class PdfEquipoObserver
{
    public function deleted(PdfEquipo $pdf): void
    {
        NotifyAdmins::dispatch(
            title: 'Documento PDF eliminado',
            body: "Se eliminó el documento «{$pdf->nombre}» — por {$this->actor()}.",
            url: PdfEquipoResource::getUrl('index'),
            icon: 'heroicon-o-trash',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    private function actor(): string
    {
        return auth()->user()?->name ?? 'Sistema';
    }
}
