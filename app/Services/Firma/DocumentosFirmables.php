<?php

namespace App\Services\Firma;

use App\Enums\CelularFormularioPdfTipo;
use App\Enums\EquipoFormularioPdfTipo;
use App\Enums\EstadoFirmaOrigen;
use App\Exceptions\FirmaDigitalException;
use App\Models\PdfCelular;
use App\Models\PdfEquipo;
use App\Models\PdfMovimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Documentos PDF ya generados en el sistema que se pueden enviar a firmar: equipos,
 * celulares y movimientos (los recibos de equipo son facturas escaneadas y no se firman).
 *
 * Cada documento se identifica con una clave "origen:id" (p. ej. "pdf_equipo:12")
 * para usarla como valor de un Select.
 */
class DocumentosFirmables
{
    /**
     * @var array<string, array{0: class-string<PdfEquipo|PdfCelular|PdfMovimiento>, 1: string}> origen => [modelo, etiqueta]
     */
    private const ORIGENES = [
        'pdf_equipo' => [PdfEquipo::class, 'Equipos'],
        'pdf_celular' => [PdfCelular::class, 'Celulares'],
        'pdf_movimiento' => [PdfMovimiento::class, 'Movimientos'],
    ];

    /**
     * Categorías para filtrar en el asistente.
     *
     * @return array<string, string> clave => etiqueta
     */
    public static function categorias(): array
    {
        return [
            'equipo_'.EquipoFormularioPdfTipo::Entrega->value => 'Equipos · Entrega',
            'equipo_'.EquipoFormularioPdfTipo::Recepcion->value => 'Equipos · Recepción',
            'equipo_'.EquipoFormularioPdfTipo::NotaSalida->value => 'Equipos · Nota de salida',
            'equipo_'.EquipoFormularioPdfTipo::NotaRecepcion->value => 'Equipos · Nota de recepción externa',
            'celular_'.CelularFormularioPdfTipo::Entrega->value => 'Celulares · Entrega',
            'celular_'.CelularFormularioPdfTipo::Recepcion->value => 'Celulares · Recepción',
            'movimiento_alta' => 'Movimientos · Alta',
            'movimiento_baja' => 'Movimientos · Baja',
        ];
    }

    /**
     * Del más reciente al más antiguo. Sin categoría busca en todos los orígenes.
     *
     * @return array<string, string> clave => etiqueta
     */
    public function buscar(?string $busqueda, ?string $categoria = null, bool $incluirFirmados = false, int $limite = 30): array
    {
        $documentos = collect();

        foreach (self::ORIGENES as $origen => [$modelo]) {
            $consulta = $this->filtrarCategoria($modelo::query(), $origen, $categoria);

            if ($consulta === null) {
                continue;
            }

            $documentos = $documentos->merge($consulta
                ->whereNotNull('url')
                ->when(! $incluirFirmados, fn (Builder $query) => $query->firmables())
                ->when(filled($busqueda), fn (Builder $query) => $query->where(fn (Builder $query) => $query
                    ->whereLike('url', "%{$busqueda}%")
                    ->orWhereLike('nombre', "%{$busqueda}%")))
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->limit($limite)
                ->get()
                ->map(fn (Model $documento): array => ['clave' => "{$origen}:{$documento->getKey()}", 'documento' => $documento]));
        }

        return $documentos
            ->sortByDesc(fn (array $item): string => ($item['documento']->getAttribute('fecha')?->format('Y-m-d') ?? '').sprintf('%010d', $item['documento']->getKey()))
            ->take($limite)
            ->mapWithKeys(fn (array $item): array => [$item['clave'] => $this->etiqueta($item['documento'])])
            ->all();
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

    public function resolver(string $clave): PdfEquipo|PdfCelular|PdfMovimiento|null
    {
        [$origen, $id] = array_pad(explode(':', $clave, 2), 2, null);
        $modelo = self::ORIGENES[$origen][0] ?? null;

        return $modelo === null || $id === null ? null : $modelo::query()->find($id);
    }

    public static function clave(Model $documento): ?string
    {
        foreach (self::ORIGENES as $origen => [$modelo]) {
            if ($documento instanceof $modelo) {
                return "{$origen}:{$documento->getKey()}";
            }
        }

        return null;
    }

    /**
     * Se puede elegir: existe y no está en firma ni firmado.
     */
    public function esElegible(string $clave): bool
    {
        return $this->resolver($clave)?->esFirmable() ?? false;
    }

    public function etiqueta(Model $documento): string
    {
        $fecha = $documento->getAttribute('fecha')?->format('d/m/Y');
        $estado = $documento->getAttribute('estado_firma');
        $marca = $estado instanceof EstadoFirmaOrigen && $estado !== EstadoFirmaOrigen::SinFirma ? " · {$estado->getLabel()}" : '';

        return $this->etiquetaOrigen($documento::class).' · '.$this->nombre($documento).($fecha ? " · {$fecha}" : '').$marca;
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

    /**
     * @param  Builder<Model>  $consulta
     * @return Builder<Model>|null null si la categoría no corresponde a este origen.
     */
    private function filtrarCategoria(Builder $consulta, string $origen, ?string $categoria): ?Builder
    {
        if (blank($categoria)) {
            return $consulta;
        }

        [$grupo, $tipo] = array_pad(explode('_', $categoria, 2), 2, '');

        return match (true) {
            $origen === 'pdf_equipo' && $grupo === 'equipo' => $consulta->where('nombre', EquipoFormularioPdfTipo::from($tipo)->nombreOficial()),
            $origen === 'pdf_celular' && $grupo === 'celular' => $consulta->where('nombre', CelularFormularioPdfTipo::from($tipo)->nombreOficial()),
            $origen === 'pdf_movimiento' && $grupo === 'movimiento' => $consulta->whereHas('movimiento', fn (Builder $query) => $query->where('tipo', ucfirst($tipo))),
            default => null,
        };
    }
}
