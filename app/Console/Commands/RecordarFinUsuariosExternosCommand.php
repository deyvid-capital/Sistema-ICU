<?php

namespace App\Console\Commands;

use App\Models\UsuarioExterno;
use App\Notifications\UsuarioExternoFinalizadoNotification;
use App\Support\Notifications\AdminRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa por correo a los administradores de los usuarios externos
 * que llegaron a su fecha fin (desde el scheduler, cada hora en horario de
 * oficina). Cada usuario externo se avisa una sola vez por fecha fin: el observer
 * vuelve a programar el aviso si la fecha cambia, y un usuario externo desactivado o
 * eliminado no se avisa.
 */
class RecordarFinUsuariosExternosCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'usuarios-externos:recordar-fin';

    /**
     * @var string
     */
    protected $description = 'Avisa a los administradores de los usuarios externos que llegaron a su fecha fin';

    public function handle(): int
    {
        $hoy = now(config('app.display_timezone'))->startOfDay();
        $administradores = AdminRecipients::all();
        $avisadas = 0;

        UsuarioExterno::query()
            ->conRecordatorioPendiente($hoy)
            ->with(['responsable', 'sitioSharepoint'])
            ->each(function (UsuarioExterno $usuarioExterno) use ($administradores, &$avisadas): void {
                Notification::send($administradores, new UsuarioExternoFinalizadoNotification($usuarioExterno));

                UsuarioExterno::query()
                    ->whereKey($usuarioExterno->getKey())
                    ->update(['recordatorio_enviado_at' => now()]);

                $avisadas++;
            });

        $this->info("Usuarios externos avisados: {$avisadas}.");

        return self::SUCCESS;
    }
}
