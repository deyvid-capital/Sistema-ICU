<?php

namespace App\Services\Firma;

use App\Exceptions\FirmaDigitalException;
use App\Models\PdfCelular;
use App\Models\PdfEquipo;
use App\Models\PdfMovimiento;
use App\Models\ReciboEquipo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Documentos PDF ya generados en el sistema que se pueden enviar a firmar.
 *
 * Cada documento se identifica con una clave "origen:id" (p. ej. "pdf_equipo:12")
 * para usarla como valor de un Select.
 */
class DocumentosFirmables
{
    /**
     * @var array<string, array{0: class-string<Model>, 1: string, 2: bool}> origen => [modelo, etiqueta, tiene columna nombre]
     */
    private const ORIGENES = [
        'pdf_equipo' => [PdfEquipo::class, 'Equipos', true],
        'pdf_celular' => [PdfCelular::class, 'Celulares', true],
        'pdf_movimiento' => [PdfMovimiento::class, 'Altas y bajas', true],
        'recibo_equipo' => [ReciboEquipo::class, 'Recibos de equipos', false],
    ];

    /**
     * @return array<string, string> clave => etiqueta
     */
    public function buscar(?string $busqueda, int $limitePorOrigen = 15): array
    {
        $resultados = [];

        foreach (self::ORIGENES as $origen => [$modelo, , $tieneNombre]) {
            $modelo::query()
                ->whereNotNull('url')
                ->when(filled($busqueda), fn (Builder $query) => $query->where(function (Builder $query) use ($busqueda, $tieneNombre): void {
                    $query->whereLike('url', "%{$busqueda}%");

                    if ($tieneNombre) {
                        $query->orWhereLike('nombre', "%{$busqueda}%");
                    }
                }))
                ->latest('id')
                ->limit($limitePorOrigen)
                ->get()
                ->each(function (Model $documento) use ($origen, &$resultados): void {
                    $resultados["{$origen}:{$documento->getKey()}"] = $this->etiqueta($documento);
                });
        }

        return $resultados;
    }

    /**
     * @param  list<string>  $claves
     * @return array<string, string>
     */
    public function etiquetas(array $claves): array
    {
        $etiquetas = [];

        foreach ($claves as $clave) {
            $documento = $this->resolver($clave);

            if ($documento !== null) {
                $etiquetas[$clave] = $this->etiqueta($documento);
            }
        }

        return $etiquetas;
    }

    public function resolver(string $clave): ?Model
    {
        [$origen, $id] = array_pad(explode(':', $clave, 2), 2, null);
        $modelo = self::ORIGENES[$origen][0] ?? null;

        return $modelo === null || $id === null ? null : $modelo::query()->find($id);
    }

    public function etiqueta(Model $documento): string
    {
        $origen = $this->origenDe($documento);
        $fecha = $documento->getAttribute('fecha')?->format('d/m/Y');

        return self::ORIGENES[$origen][1].' · '.$this->nombre($documento).($fecha ? " · {$fecha}" : '');
    }

    public function nombre(Model $documento): string
    {
        return basename((string) $documento->getAttribute('url'));
    }

    /**
     * @throws FirmaDigitalException si el archivo ya no está en el disco.
     */
    public function contenido(Model $documento): string
    {
        $disco = Storage::disk($documento::documentDisk());
        $url = (string) $documento->getAttribute('url');

        if ($url === '' || ! $disco->exists($url)) {
            throw FirmaDigitalException::documentoNoDisponible($this->nombre($documento));
        }

        return (string) $disco->get($url);
    }

    public function etiquetaOrigen(?string $clase): string
    {
        foreach (self::ORIGENES as [$modelo, $etiqueta]) {
            if ($modelo === $clase) {
                return $etiqueta;
            }
        }

        return 'PDF subido';
    }

    private function origenDe(Model $documento): string
    {
        foreach (self::ORIGENES as $origen => [$modelo]) {
            if ($documento instanceof $modelo) {
                return $origen;
            }
        }

        throw new \InvalidArgumentException('Tipo de documento no firmable: '.$documento::class);
    }
}
