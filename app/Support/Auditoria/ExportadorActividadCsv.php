<?php

namespace App\Support\Auditoria;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Descarga el registro de actividad como CSV, en una sola petición.
 *
 * Las filas se emiten según se leen (`lazyByIdDesc`), de modo que el consumo de
 * memoria no depende del tamaño del log. Cada evento es UNA fila: los campos
 * multilínea (Actor/origen, Detalle consolidado) van entre comillas, que es
 * como fputcsv encierra cualquier valor con salto de línea — el salto queda
 * dentro de la celda y no abre una fila nueva al abrir el archivo en Excel o
 * LibreOffice.
 */
class ExportadorActividadCsv
{
    /**
     * Separador de columnas: punto y coma en vez de coma, para que Excel en
     * configuración regional es-ES (donde la coma es el separador decimal) abra
     * el archivo con las columnas ya divididas, sin pasar por el asistente de
     * importación.
     */
    private const SEPARADOR = ';';

    /**
     * Propiedades con tratamiento propio, que no se repiten en «Detalle».
     *
     * @var list<string>
     */
    private const PROPIEDADES_PROPIAS = ['email', 'ip'];

    /**
     * @return list<string>
     */
    public function cabeceras(): array
    {
        return [
            'Fecha',
            'Categoría',
            'Acción / resultado',
            'Actor / origen',
            'Sujeto',
            'Descripción',
            'Detalle consolidado',
        ];
    }

    public function descargar(Builder $consulta, ?string $nombre = null): StreamedResponse
    {
        $nombre ??= 'registro-actividad-'.now()
            ->timezone(config('app.display_timezone'))
            ->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($consulta): void {
            $salida = fopen('php://output', 'w');

            // Excel solo interpreta el acento y la eñe si el archivo abre con BOM.
            fwrite($salida, "\xEF\xBB\xBF");

            // `escape: ''` desactiva el escapado con barra invertida, que no es
            // parte del CSV estándar. En PHP 8.5 omitirlo está deprecado. Las
            // comillas dentro de un valor se duplican («""»), que es el propio
            // comportamiento del enclosure y no depende de este parámetro.
            fputcsv($salida, $this->cabeceras(), self::SEPARADOR, escape: '');

            foreach ($consulta->lazyByIdDesc(500) as $registro) {
                fputcsv($salida, $this->fila($registro), self::SEPARADOR, escape: '');
            }

            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return list<string>
     */
    public function fila(Activity $registro): array
    {
        $propiedades = $registro->properties?->all() ?? [];
        $cambios = $registro->attribute_changes?->all() ?? [];

        return [
            $registro->created_at?->timezone(config('app.display_timezone'))->format('d/m/Y H:i:s') ?? '',
            EtiquetasActividad::dominio($registro->log_name),
            $this->accionResultado($registro),
            $this->actorOrigen($registro, $propiedades),
            $this->sujeto($registro),
            (string) $registro->description,
            $this->detalleConsolidado($cambios, $propiedades),
        ];
    }

    /**
     * Evento y su desenlace en una sola celda. Los eventos de cambios de datos
     * no tienen resultado (Éxito/Fallo/Bloqueo es un concepto solo de seguridad),
     * así que esa mitad se omite en vez de dejarla vacía.
     */
    private function accionResultado(Activity $registro): string
    {
        $evento = EtiquetasActividad::evento($registro->event);
        $resultado = EtiquetasActividad::resultado($registro->event);

        return $resultado !== '' ? "{$evento} · {$resultado}" : $evento;
    }

    /**
     * Usuario, correo e IP apilados en la misma celda, una línea cada uno. Se
     * omite la línea de cualquier dato que falte (un cambio de datos no tiene
     * IP; un correo desconocido no tiene usuario) en vez de dejar líneas vacías.
     *
     * @param  array<string, mixed>  $propiedades
     */
    private function actorOrigen(Activity $registro, array $propiedades): string
    {
        $lineas = array_filter([
            $registro->causer?->name,
            $propiedades['email'] ?? null,
            $propiedades['ip'] ?? null,
        ], fn (mixed $valor): bool => filled($valor));

        return implode("\n", $lineas);
    }

    private function sujeto(Activity $registro): string
    {
        if (blank($registro->subject_type)) {
            return '';
        }

        $tipo = Str::of($registro->subject_type)->afterLast('\\')->headline()->toString();

        return trim($tipo.' #'.$registro->subject_id);
    }

    /**
     * Cambios de campos y resto de propiedades en una sola celda, cada bloque
     * bajo su propio encabezado y cada dato en su propia línea.
     *
     * @param  array{old?: mixed, attributes?: mixed}  $cambios
     * @param  array<string, mixed>  $propiedades
     */
    private function detalleConsolidado(array $cambios, array $propiedades): string
    {
        $bloques = array_filter([
            'Cambios' => $this->cambios($cambios),
            'Detalle' => $this->detalle($propiedades),
        ], fn (string $contenido): bool => $contenido !== '');

        $partes = [];

        foreach ($bloques as $encabezado => $contenido) {
            $partes[] = "{$encabezado}:\n{$contenido}";
        }

        return implode("\n", $partes);
    }

    /**
     * Diferencia legible: «campo: antes → después», con los nombres de campo en
     * español.
     *
     * @param  array{old?: mixed, attributes?: mixed}  $cambios
     */
    private function cambios(array $cambios): string
    {
        $nuevos = Arr::wrap($cambios['attributes'] ?? []);

        if ($nuevos === []) {
            return '';
        }

        $anteriores = Arr::wrap($cambios['old'] ?? []);

        $lineas = [];

        foreach ($nuevos as $campo => $despues) {
            $etiqueta = EtiquetasActividad::campo((string) $campo);

            // En un alta no hay valor previo: la flecha desde «(vacío)» solo
            // añadiría ruido a todas las columnas.
            if (! array_key_exists($campo, $anteriores)) {
                $lineas[] = $etiqueta.': '.$this->escalar($despues);

                continue;
            }

            $lineas[] = $etiqueta.': '
                .$this->escalar($anteriores[$campo]).' → '.$this->escalar($despues);
        }

        return implode("\n", $lineas);
    }

    /**
     * Resto de propiedades (motivo, intentos restantes, archivos, navegador...).
     *
     * @param  array<string, mixed>  $propiedades
     */
    private function detalle(array $propiedades): string
    {
        $restantes = Arr::except($propiedades, self::PROPIEDADES_PROPIAS);

        $lineas = [];

        foreach ($restantes as $clave => $valor) {
            if (blank($valor) && ! is_bool($valor)) {
                continue;
            }

            $lineas[] = EtiquetasActividad::campo((string) $clave).': '.$this->escalar($valor);
        }

        return implode("\n", $lineas);
    }

    private function escalar(mixed $valor): string
    {
        return match (true) {
            $valor === null, $valor === '' => '(vacío)',
            is_bool($valor) => $valor ? 'sí' : 'no',
            is_scalar($valor) => (string) $valor,
            default => (string) json_encode($valor, JSON_UNESCAPED_UNICODE),
        };
    }
}
