<?php

namespace App\Filament\Resources\SoftwareAreas\Pages;

use App\Filament\Resources\SoftwareAreas\SoftwareAreaResource;
use App\Models\SoftwareArea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSoftwareArea extends CreateRecord
{
    protected static string $resource = SoftwareAreaResource::class;

    protected static bool $canCreateAnother = false;

    protected int $cantidadAsignada = 0;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $areaId = (int) $data['area_id'];
        $softwareIds = collect($data['software_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($softwareIds->isEmpty()) {
            throw ValidationException::withMessages([
                'data.software_ids' => 'Selecciona al menos un software.',
            ]);
        }

        $ultimo = null;

        foreach ($softwareIds as $softwareId) {
            $ultimo = SoftwareArea::query()->create([
                'software_id' => $softwareId,
                'area_id' => $areaId,
            ]);
        }

        $this->cantidadAsignada = $softwareIds->count();

        return $ultimo;
    }

    protected function getCreatedNotification(): ?Notification
    {
        $cantidad = $this->cantidadAsignada;
        $detalle = $cantidad === 1
            ? 'Se asignó 1 software al área.'
            : "Se asignaron {$cantidad} software al área.";

        return Notification::make()
            ->success()
            ->title('Relaciones registradas')
            ->body($detalle);
    }
}
