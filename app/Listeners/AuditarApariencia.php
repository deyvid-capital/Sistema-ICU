<?php

namespace App\Listeners;

use App\Enums\SeccionConfiguracion;
use App\Support\Auditoria\AuditorConfiguracion;
use CapitalSafi\Apariencia\Events\AparienciaActualizada;

class AuditarApariencia
{
    public function __construct(private readonly AuditorConfiguracion $auditor) {}

    /**
     * Solo la apariencia global es una configuración del sistema; la personal
     * de cada usuario no se audita.
     */
    public function handle(AparienciaActualizada $evento): void
    {
        if ($evento->alcance !== 'global') {
            return;
        }

        $this->auditor->cambiado(
            SeccionConfiguracion::Apariencia,
            restaurado: $evento->accion === 'restablecido',
            antes: $evento->antes,
            despues: $evento->despues,
            extra: $this->archivos($evento->archivos),
        );
    }

    /**
     * @param  array{subidos?: array<string, string>, eliminados?: array<string, string>}  $archivos
     * @return array{archivos?: array{subidos?: array<string, string>, eliminados?: array<string, string>}}
     */
    private function archivos(array $archivos): array
    {
        $nombres = array_filter([
            'subidos' => array_map('basename', $archivos['subidos'] ?? []),
            'eliminados' => array_map('basename', $archivos['eliminados'] ?? []),
        ]);

        return $nombres === [] ? [] : ['archivos' => $nombres];
    }
}
