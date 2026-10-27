<?php

namespace App\Console\Commands;

use App\Enums\EstadoSolicitudFirma;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaFirma;
use App\Models\SolicitudFirmaFirmante;
use App\Models\User;
use App\Notifications\Firma\FirmaSolicitadaNotification;
use App\Services\Firma\AvisosSolicitudFirma;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Recordatorios de firmas pendientes (cada hora desde el scheduler). Reglas en
 * Docs/firma-digital/06-notificaciones.md: solo envíos con recordatorio, solo a quien
 * puede firmar ahora, solo en horario hábil y, con el plazo vencido, copia al creador.
 */
class EnviarRecordatoriosFirmaCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'firmas:enviar-recordatorios
                            {--fuera-de-horario : Envía aunque sea fuera del horario hábil (para pruebas)}';

    /**
     * @var string
     */
    protected $description = 'Recuerda a los firmantes sus firmas pendientes según el intervalo de cada envío';

    public function handle(AvisosSolicitudFirma $avisos): int
    {
        if (! $this->option('fuera-de-horario') && ! $this->enHorarioHabil()) {
            $this->info('Fuera del horario hábil: no se envían recordatorios.');

            return self::SUCCESS;
        }

        $enviados = 0;

        SolicitudFirma::query()
            ->where('estado', EstadoSolicitudFirma::EnCurso)
            ->whereNotNull('recordatorio_cada_horas')
            ->with(['firmantes.usuario', 'creador'])
            ->each(function (SolicitudFirma $solicitud) use ($avisos, &$enviados): void {
                $vencido = $this->plazoVencido($solicitud);
                $recordados = $this->recordar($solicitud, $vencido);

                if ($vencido) {
                    $avisos->plazoVencido($solicitud, $recordados);
                }

                $enviados += $recordados->count();
            });

        $this->info("Recordatorios enviados: {$enviados}.");

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, User>
     */
    private function recordar(SolicitudFirma $solicitud, bool $vencido): Collection
    {
        $recordados = new Collection;

        foreach ($solicitud->firmantes as $firmante) {
            /** @var SolicitudFirmaFirmante $firmante */
            $usuario = $firmante->usuario;

            if ($usuario === null || ! $this->tocaRecordar($solicitud, $firmante) || ! $this->puedeFirmarAhora($solicitud, $usuario)) {
                continue;
            }

            $firmante->update(['ultimo_recordatorio_at' => now()]);
            $usuario->notify(new FirmaSolicitadaNotification($solicitud, esRecordatorio: true, vencido: $vencido));
            $recordados->push($usuario);
        }

        return $recordados;
    }

    /**
     * Pasaron las horas configuradas desde el último aviso (o desde el envío).
     */
    private function tocaRecordar(SolicitudFirma $solicitud, SolicitudFirmaFirmante $firmante): bool
    {
        $ultimoAviso = $firmante->ultimo_recordatorio_at ?? $solicitud->enviada_at;

        return $ultimoAviso !== null
            && $ultimoAviso->lte(now()->subHours($solicitud->recordatorio_cada_horas));
    }

    /**
     * Orden libre: cualquier firma pendiente. Con orden: solo si ya le toca.
     */
    private function puedeFirmarAhora(SolicitudFirma $solicitud, User $usuario): bool
    {
        return $solicitud->firmasPendientesDe($usuario)
            ->get()
            ->contains(fn (SolicitudFirmaFirma $firma): bool => $firma->leToca());
    }

    private function plazoVencido(SolicitudFirma $solicitud): bool
    {
        return $solicitud->fecha_limite !== null
            && $solicitud->fecha_limite->toDateString() < now(config('firma.avisos.zona_horaria'))->toDateString();
    }

    private function enHorarioHabil(): bool
    {
        $ahora = now(config('firma.avisos.zona_horaria'));

        return $ahora->isWeekday()
            && $ahora->hour >= config('firma.avisos.hora_inicio')
            && $ahora->hour < config('firma.avisos.hora_fin');
    }
}
