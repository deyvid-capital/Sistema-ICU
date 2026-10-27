<?php

namespace App\Console\Commands;

use App\Enums\EstadoCertificadoFirma;
use App\Models\CertificadoFirma;
use App\Notifications\Firma\CertificadoPorVencerNotification;
use App\Support\Notifications\AdminRecipients;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa al titular y a los admins que un certificado vence pronto (cada día desde el
 * scheduler). Cada umbral (30, 15 y 7 días) se avisa una sola vez por certificado.
 */
class AvisarVencimientosFirmaCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'firmas:avisar-vencimientos';

    /**
     * @var string
     */
    protected $description = 'Avisa de las firmas digitales cuyo certificado vence en 30, 15 o 7 días';

    public function handle(): int
    {
        /** @var list<int> $umbrales */
        $umbrales = collect(config('firma.avisos.dias_vencimiento'))->map(fn ($dias): int => (int) $dias)->sort()->values()->all();
        $avisados = 0;

        CertificadoFirma::query()
            ->whereIn('estado', [EstadoCertificadoFirma::Activo, EstadoCertificadoFirma::RequiereReactivacion])
            ->where('vigente_hasta', '>=', now()->startOfDay())
            ->where('vigente_hasta', '<', now()->startOfDay()->addDays(max($umbrales) + 1))
            ->with('usuario')
            ->each(function (CertificadoFirma $certificado) use ($umbrales, &$avisados): void {
                $dias = (int) $certificado->diasParaVencer();
                $umbral = collect($umbrales)->first(fn (int $limite): bool => $dias <= $limite);

                if ($umbral === null || $this->yaAvisado($certificado, $umbral) || $this->tieneRenovacionPendiente($certificado)) {
                    return;
                }

                CertificadoFirma::query()->whereKey($certificado->getKey())->update(['aviso_vencimiento_dias' => $umbral]);
                $this->avisar($certificado, $dias);
                $avisados++;
            });

        $this->info("Firmas por vencer avisadas: {$avisados}.");

        return self::SUCCESS;
    }

    private function yaAvisado(CertificadoFirma $certificado, int $umbral): bool
    {
        return $certificado->aviso_vencimiento_dias !== null && $certificado->aviso_vencimiento_dias <= $umbral;
    }

    /**
     * Si el admin ya subió el .p12 nuevo, el titular solo tiene que activarlo (ya se le avisó).
     */
    private function tieneRenovacionPendiente(CertificadoFirma $certificado): bool
    {
        return CertificadoFirma::query()
            ->where('reemplaza_a_id', $certificado->getKey())
            ->where('estado', EstadoCertificadoFirma::PendienteActivacion)
            ->exists();
    }

    private function avisar(CertificadoFirma $certificado, int $dias): void
    {
        $titular = $certificado->usuario;
        $venceEl = $certificado->vigente_hasta->timezone(config('app.display_timezone'))->format('d/m/Y');
        $nombre = $titular->name ?? $certificado->nombre_en_sello;

        $titular?->notify(new CertificadoPorVencerNotification($nombre, $venceEl, $dias));

        Notification::send(
            AdminRecipients::all($titular),
            new CertificadoPorVencerNotification($nombre, $venceEl, $dias, paraAdministrador: true),
        );
    }
}
