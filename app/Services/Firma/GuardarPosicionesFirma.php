<?php

namespace App\Services\Firma;

use App\Enums\EstadoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Exceptions\FirmaDigitalException;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use App\Models\SolicitudFirmaFirmante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Guarda los recuadros de firma ubicados en el canvas. Las posiciones vienen del
 * navegador, así que se validan todas: documento y firmante del envío, página
 * existente, recuadro dentro de la página y sin superposiciones.
 *
 * Se puede guardar a medias; enviar exige que todos los firmantes estén ubicados
 * en todos los documentos ({@see self::validarCompletas()}).
 */
class GuardarPosicionesFirma
{
    /**
     * @param  list<array{documento_id: int|string, firmante_id: int|string, pagina: int|string, x: float|int|string, y: float|int|string}>  $posiciones
     *
     * @throws FirmaDigitalException
     */
    public function __invoke(SolicitudFirma $solicitud, array $posiciones): void
    {
        if ($solicitud->estado !== EstadoSolicitudFirma::Borrador) {
            throw FirmaDigitalException::solicitudNoEsBorrador();
        }

        $documentos = $solicitud->documentos()->get()->keyBy('id');
        $firmantes = $solicitud->firmantes()->with('usuario')->get()->keyBy('id');
        $recuadros = $this->normalizar($posiciones, $documentos, $firmantes);

        DB::transaction(function () use ($documentos, $recuadros): void {
            foreach ($documentos as $documento) {
                $documento->firmas()->delete();
            }

            foreach ($recuadros as $recuadro) {
                $documentos[$recuadro['documento_id']]->firmas()->create([
                    ...$recuadro,
                    'campo' => 'firma_'.$recuadro['firmante_id'],
                    'estado' => EstadoFirma::Pendiente,
                ]);
            }
        });
    }

    /**
     * @throws FirmaDigitalException si a algún documento le falta el recuadro de algún firmante.
     */
    public function validarCompletas(SolicitudFirma $solicitud): void
    {
        $firmantes = $solicitud->firmantes()->with('usuario')->get();
        $faltantes = [];

        foreach ($solicitud->documentos()->with('firmas')->get() as $documento) {
            $ubicados = $documento->firmas->pluck('firmante_id')->all();

            foreach ($firmantes as $firmante) {
                if (! in_array($firmante->id, $ubicados, true)) {
                    $faltantes[] = "{$firmante->usuario?->name} en {$documento->nombre}";
                }
            }
        }

        if ($faltantes !== []) {
            throw FirmaDigitalException::posicionesIncompletas($faltantes);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $posiciones
     * @param  Collection<int, SolicitudFirmaDocumento>  $documentos
     * @param  Collection<int, SolicitudFirmaFirmante>  $firmantes
     * @return list<array{documento_id: int, firmante_id: int, pagina: int, x: float, y: float, ancho: float, alto: float}>
     *
     * @throws FirmaDigitalException
     */
    private function normalizar(array $posiciones, Collection $documentos, Collection $firmantes): array
    {
        $ancho = (float) config('firma.sello.ancho');
        $alto = (float) config('firma.sello.alto');
        $recuadros = [];

        foreach ($posiciones as $posicion) {
            $documento = $documentos->get((int) ($posicion['documento_id'] ?? 0));
            $firmante = $firmantes->get((int) ($posicion['firmante_id'] ?? 0));

            if ($documento === null || $firmante === null) {
                throw FirmaDigitalException::posicionInvalida();
            }

            $clave = "{$documento->id}:{$firmante->id}";
            $pagina = (int) ($posicion['pagina'] ?? 0);
            $x = round((float) ($posicion['x'] ?? -1), 2);
            $y = round((float) ($posicion['y'] ?? -1), 2);
            $tamano = $documento->paginas[$pagina - 1] ?? null;

            if (isset($recuadros[$clave]) || $tamano === null) {
                throw FirmaDigitalException::posicionInvalida();
            }

            // Medio punto de tolerancia por el redondeo del navegador.
            if ($x < -0.5 || $y < -0.5 || $x + $ancho > $tamano['ancho'] + 0.5 || $y + $alto > $tamano['alto'] + 0.5) {
                throw FirmaDigitalException::posicionFueraDePagina((string) $firmante->usuario?->name, $documento->nombre);
            }

            $recuadros[$clave] = [
                'documento_id' => $documento->id,
                'firmante_id' => $firmante->id,
                'pagina' => $pagina,
                'x' => max($x, 0),
                'y' => max($y, 0),
                'ancho' => $ancho,
                'alto' => $alto,
            ];
        }

        $this->validarSinSuperposiciones(array_values($recuadros), $documentos);

        return array_values($recuadros);
    }

    /**
     * @param  list<array{documento_id: int, pagina: int, x: float, y: float, ancho: float, alto: float}>  $recuadros
     * @param  Collection<int, SolicitudFirmaDocumento>  $documentos
     *
     * @throws FirmaDigitalException
     */
    private function validarSinSuperposiciones(array $recuadros, Collection $documentos): void
    {
        foreach ($recuadros as $i => $a) {
            foreach (array_slice($recuadros, $i + 1) as $b) {
                $mismaPagina = $a['documento_id'] === $b['documento_id'] && $a['pagina'] === $b['pagina'];

                if ($mismaPagina
                    && $a['x'] < $b['x'] + $b['ancho'] && $b['x'] < $a['x'] + $a['ancho']
                    && $a['y'] < $b['y'] + $b['alto'] && $b['y'] < $a['y'] + $a['alto']) {
                    throw FirmaDigitalException::posicionesSuperpuestas($documentos[$a['documento_id']]->nombre, $a['pagina']);
                }
            }
        }
    }
}
