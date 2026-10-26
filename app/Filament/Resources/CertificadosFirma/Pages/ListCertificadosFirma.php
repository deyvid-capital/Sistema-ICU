<?php

namespace App\Filament\Resources\CertificadosFirma\Pages;

use App\Filament\Resources\CertificadosFirma\Actions\CertificadoFirmaActions;
use App\Filament\Resources\CertificadosFirma\CertificadoFirmaResource;
use Filament\Resources\Pages\ListRecords;

class ListCertificadosFirma extends ListRecords
{
    protected static string $resource = CertificadoFirmaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CertificadoFirmaActions::asignar(),
        ];
    }
}
