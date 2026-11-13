<?php

namespace App\Observers;

use App\Filament\Resources\Users\UserResource;
use App\Models\Movimiento;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Services\Movimientos\ProcesarBajaColaborador;
use App\Services\Movimientos\ReasignarTrasReingreso;
use App\Services\Movimientos\ResumenBajaColaborador;
use App\Support\Notifications\NotifyAdmins;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * Al registrar una Baja da de baja en cascada todo lo asignado al colaborador.
 *
 * La cascada es unidireccional por decisión de negocio: un Alta posterior no
 * reactiva nada por sí sola y eliminar el movimiento de Baja tampoco la revierte.
 *
 * Al registrar un Alta de alguien que tuvo una Baja (reingreso) solo se avisa
 * de que puede devolvérsele todo con la acción «Reasignar todo» de su ficha.
 */
class MovimientoObserver
{
    public function __construct(
        private ProcesarBajaColaborador $procesarBaja,
        private ReasignarTrasReingreso $reasignar,
    ) {}

    public function created(Movimiento $movimiento): void
    {
        $this->procesarSiEsBaja($movimiento);
        $this->avisarSiEsReingreso($movimiento);
    }

    /**
     * Cubre la corrección de un movimiento de Alta a Baja desde EditMovimiento.
     * El servicio es idempotente, así que reprocesar es seguro.
     */
    public function updated(Movimiento $movimiento): void
    {
        if ($movimiento->wasChanged('tipo')) {
            $this->procesarSiEsBaja($movimiento);
            $this->avisarSiEsReingreso($movimiento);
        }
    }

    /**
     * No devuelve nada: solo avisa a quien registró el Alta de que puede hacerlo.
     */
    private function avisarSiEsReingreso(Movimiento $movimiento): void
    {
        if ($movimiento->tipo !== 'Alta' || blank($movimiento->usuario_id) || auth()->user() === null) {
            return;
        }

        $usuario = User::query()->find($movimiento->usuario_id);

        if ($usuario === null) {
            return;
        }

        $pendiente = $this->reasignar->pendiente($usuario);

        if (! $pendiente->hayAlgo()) {
            return;
        }

        Notification::make()
            ->title('Reingreso detectado')
            ->body("{$usuario->name} tuvo una baja antes. Puedes devolverle {$pendiente->detalle()} con «Reasignar todo» en su ficha.")
            ->info()
            ->persistent()
            ->actions([
                Action::make('abrirFicha')
                    ->label('Abrir su ficha')
                    ->url(UserResource::getUrl('view', ['record' => $usuario->getKey()])),
            ])
            ->send();
    }

    private function procesarSiEsBaja(Movimiento $movimiento): void
    {
        if ($movimiento->tipo !== 'Baja' || blank($movimiento->usuario_id)) {
            return;
        }

        $resumen = $this->procesarBaja->paraMovimiento($movimiento);

        if (! $resumen->huboCambios()) {
            return;
        }

        $this->notificar($movimiento, $resumen);
    }

    private function notificar(Movimiento $movimiento, ResumenBajaColaborador $resumen): void
    {
        $usuario = $this->usuario($movimiento);

        // Sin actor autenticado no hay a quién avisar: seeders y comandos crean
        // movimientos fuera del panel.
        if (true) {
            Notification::make()
                ->title('Baja procesada')
                ->body("{$resumen->mensaje()} Revisa la ficha de {$usuario} si necesitas ajustar algo.")
                ->warning()
                ->duration(10000)
                ->send();
        }

        NotifyAdmins::dispatch(
            title: 'Baja de colaborador procesada',
            body: "Se dieron de baja los accesos y asignaciones de {$usuario}: {$resumen->mensaje()} — por {$this->actor()}.",
            url: UserResource::getUrl('view', ['record' => $movimiento->usuario_id]),
            icon: 'heroicon-o-user-minus',
            color: 'danger',
            severity: CriticalEventNotification::SEVERIDAD_ALTA,
        );
    }

    private function usuario(Movimiento $movimiento): string
    {
        return User::withTrashed()->find($movimiento->usuario_id)?->name ?? (string) $movimiento->usuario_id;
    }

    private function actor(): string
    {
        return auth()->user()?->name ?? 'Sistema';
    }
}
