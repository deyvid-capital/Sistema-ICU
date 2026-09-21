<?php

namespace App\Services\Licencias;

use App\Filament\Resources\Licencias\LicenciaResource;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Notifications\CriticalEventNotification;
use App\Support\Notifications\NotifyAdmins;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GestionarCupoLicencia
{
    public function assertPuedeConsumir(Licencia $licencia, ?LicenciaUsuario $actual = null): void
    {
        DB::transaction(function () use ($licencia, $actual): void {
            $locked = Licencia::query()
                ->whereKey($licencia->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null) {
                return;
            }

            if (! $locked->tieneCupoDisponible($actual)) {
                throw new InvalidArgumentException($this->mensajeSinCupo($locked, $actual));
            }
        });
    }

    public function assertPuedeReducirTotal(Licencia $licencia, int $nuevoTotal): void
    {
        $asignadas = $licencia->contarAsignadas();

        if ($nuevoTotal >= $asignadas) {
            return;
        }

        $faltan = $asignadas - $nuevoTotal;
        $usuarios = $faltan === 1 ? 'usuario' : 'usuarios';

        throw new InvalidArgumentException(
            "Hay {$asignadas} licencias asignadas. Desasigne {$faltan} {$usuarios} antes de reducir el total a {$nuevoTotal}.",
        );
    }

    public function haltarSiNoPuedeConsumir(
        Licencia $licencia,
        ?LicenciaUsuario $actual = null,
        string $title = 'No hay licencias disponibles',
    ): void {
        try {
            $this->assertPuedeConsumir($licencia, $actual);
        } catch (InvalidArgumentException $exception) {
            $this->notificarYHalt($title, $exception);
        }
    }

    public function haltarSiNoPuedeReducir(Licencia $licencia, int $nuevoTotal): void
    {
        try {
            $this->assertPuedeReducirTotal($licencia, $nuevoTotal);
        } catch (InvalidArgumentException $exception) {
            $this->notificarYHalt('No se puede reducir el total', $exception);
        }
    }

    public static function haltarRestoreDeAsignacion(LicenciaUsuario $record): void
    {
        $licencia = $record->licencia;

        if ($licencia === null) {
            return;
        }

        app(self::class)->haltarSiNoPuedeConsumir($licencia, $record, 'No se puede restaurar');
    }

    public function notificarSiSeAgoto(Licencia $licencia): void
    {
        $licencia->refresh();

        if ($licencia->disponibles > 0) {
            return;
        }

        NotifyAdmins::dispatch(
            title: "Cupo agotado: {$licencia->nombre_licencia}",
            body: "{$licencia->nombre_licencia} ({$licencia->asignadas}/{$licencia->total_licencias}). No se pueden asignar más usuarios hasta desasignar o aumentar el total.",
            url: LicenciaResource::getUrl('edit', ['record' => $licencia]),
            icon: 'heroicon-o-key',
            color: 'warning',
            severity: CriticalEventNotification::SEVERIDAD_MEDIA,
        );
    }

    public function mensajeSinCupo(Licencia $licencia, ?LicenciaUsuario $actual = null): string
    {
        $asignadas = $licencia->contarAsignadas($actual);
        $total = (int) $licencia->total_licencias;

        return "No hay licencias disponibles de {$licencia->nombre_licencia}. Total: {$total}, asignadas: {$asignadas}. Desasigne un usuario o aumente el total.";
    }

    private function notificarYHalt(string $title, InvalidArgumentException $exception): never
    {
        Notification::make()
            ->danger()
            ->title($title)
            ->body($exception->getMessage())
            ->persistent()
            ->send();

        throw new Halt;
    }
}
