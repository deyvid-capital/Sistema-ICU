<?php

namespace Tests\Feature\Firma\Concerns;

use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaFirma;
use App\Models\User;
use App\Services\Firma\CrearSolicitudFirma;
use App\Services\Firma\EnviarSolicitudFirma;
use App\Services\Firma\GuardarPosicionesFirma;
use Spatie\Permission\Models\Role;

/**
 * Envíos ya enviados (en curso) armados con los servicios reales y el firmador simulado.
 * Requiere FakeFirmador.
 */
trait PreparaEnviosEnCurso
{
    protected function firmanteConFirma(string $nombre): User
    {
        $usuario = $this->titular(['name' => $nombre]);
        $usuario->assignRole(Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']));
        $this->certificadoActivo($usuario);

        return $usuario;
    }

    /**
     * @param  list<User>  $firmantes
     */
    protected function envioEnCurso(User $creador, array $firmantes, int $documentos = 1, bool $ordenObligatorio = false): SolicitudFirma
    {
        $solicitud = app(CrearSolicitudFirma::class)(
            $creador,
            ['asunto' => 'Acta de entrega', 'orden_obligatorio' => $ordenObligatorio],
            array_map(fn (int $i): array => ['nombre' => "acta-{$i}.pdf", 'contenido' => $this->pdfDePrueba()], range(1, $documentos)),
            array_map(fn (User $usuario): string => (string) $usuario->id, $firmantes),
        );

        $posiciones = [];

        foreach ($solicitud->documentos as $documento) {
            foreach ($solicitud->firmantes as $indice => $firmante) {
                $posiciones[] = [
                    'documento_id' => $documento->id,
                    'firmante_id' => $firmante->id,
                    'pagina' => 1,
                    'x' => 20 + $indice * (config('firma.sello.ancho') + 10),
                    'y' => 60,
                ];
            }
        }

        app(GuardarPosicionesFirma::class)($solicitud, $posiciones);
        app(EnviarSolicitudFirma::class)($solicitud);

        return $solicitud->refresh();
    }

    protected function firmaDe(SolicitudFirma $solicitud, User $usuario, int $documento = 0): SolicitudFirmaFirma
    {
        $documentoId = $solicitud->documentos()->orderBy('id')->get()[$documento]->id;

        return SolicitudFirmaFirma::query()
            ->where('documento_id', $documentoId)
            ->whereHas('firmante', fn ($query) => $query->where('usuario_id', $usuario->id))
            ->sole();
    }
}
