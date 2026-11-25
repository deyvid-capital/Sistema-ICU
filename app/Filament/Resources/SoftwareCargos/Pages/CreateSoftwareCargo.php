<?php

namespace App\Filament\Resources\SoftwareCargos\Pages;

use App\Filament\Resources\SoftwareCargos\SoftwareCargoResource;
use App\Models\SoftwareCargo;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSoftwareCargo extends CreateRecord
{
    protected static string $resource = SoftwareCargoResource::class;

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
        $cargoId = (int) $data['cargo_id'];
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
            $ultimo = SoftwareCargo::query()->create([
                'software_id' => $softwareId,
                'cargo_id' => $cargoId,
            ]);
        }

        $this->cantidadAsignada = $softwareIds->count();

        return $ultimo;
    }

    protected function getCreatedNotification(): ?Notification
    {
        $cantidad = $this->cantidadAsignada;
        $detalle = $cantidad === 1
            ? 'Se asignó 1 software al cargo.'
            : "Se asignaron {$cantidad} software al cargo.";

        return Notification::make()
            ->success()
            ->title('Relaciones registradas')
            ->body($detalle);
    }
}
