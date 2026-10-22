<?php

namespace App\Support\Auditoria;

use App\Enums\DominioActividad;
use App\Enums\SeccionConfiguracion;
use App\Models\User;
use App\Support\Auditoria\Concerns\CapturaContextoPeticion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Escribe los cambios del panel de Configuración en `activity_log` bajo el
 * dominio "configuracion".
 *
 * El diff va en `attribute_changes` con las claves `old` y `attributes`, igual
 * que lo escribe el trait LogsIcuActivity, así la vista de detalle del plugin
 * muestra el cambio campo a campo sin código adicional.
 */
class AuditorConfiguracion
{
    use CapturaContextoPeticion;

    /**
     * Nunca forman parte del diff: no aportan nada y `updated_at` cambia siempre.
     *
     * @var list<string>
     */
    private const IGNORADOS = ['id', 'created_at', 'updated_at'];

    /**
     * @param  array<string, mixed>  $original  getOriginal() capturado ANTES del save()
     * @param  list<string>  $excepto  Atributos que no deben registrarse (secretos)
     * @param  array<string, mixed>  $extra  Propiedades adicionales (archivos, banderas)
     * @param  array<string, mixed>|null  $cambios  getChanges() capturado a mano, para
     *                                              páginas que encadenan varios save()
     */
    public function guardado(
        SeccionConfiguracion $seccion,
        Model $configuracion,
        array $original,
        array $excepto = [],
        array $extra = [],
        ?array $cambios = null,
    ): void {
        $this->escribir(
            seccion: $seccion,
            configuracion: $configuracion,
            original: $original,
            evento: 'updated',
            descripcion: $seccion->descripcionActualizado(),
            excepto: $excepto,
            extra: $extra,
            cambios: $cambios,
        );
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  list<string>  $excepto
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>|null  $cambios
     */
    public function restaurado(
        SeccionConfiguracion $seccion,
        Model $configuracion,
        array $original,
        array $excepto = [],
        array $extra = [],
        ?array $cambios = null,
    ): void {
        $this->escribir(
            seccion: $seccion,
            configuracion: $configuracion,
            original: $original,
            evento: 'restored',
            descripcion: $seccion->descripcionRestaurado(),
            excepto: $excepto,
            extra: $extra,
            cambios: $cambios,
        );
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  list<string>  $excepto
     * @param  array<string, mixed>  $extra
     * @param  array<string, mixed>|null  $cambios
     */
    private function escribir(
        SeccionConfiguracion $seccion,
        Model $configuracion,
        array $original,
        string $evento,
        string $descripcion,
        array $excepto,
        array $extra,
        ?array $cambios = null,
    ): void {
        $ignorar = array_merge(self::IGNORADOS, $excepto);

        $cambios = Arr::except($cambios ?? $this->cambiosDe($configuracion), $ignorar);

        // Equivalente a dontLogEmptyChanges(): guardar sin tocar nada no deja rastro.
        if ($cambios === [] && $extra === []) {
            return;
        }

        $anteriores = Arr::only(Arr::except($original, $ignorar), array_keys($cambios));

        activity(DominioActividad::Configuracion->value)
            ->event($evento)
            ->performedOn($configuracion)
            ->causedBy($this->usuarioAutenticado())
            ->withChanges([
                'old' => $anteriores,
                'attributes' => $cambios,
            ])
            ->withProperties(array_merge($this->contextoPeticion(), [
                'seccion' => $seccion->value,
            ], $extra))
            ->log($descripcion);
    }

    /**
     * Eloquent solo puebla getChanges() en un UPDATE. Estas tablas arrancan
     * vacías, así que el primer guardado es un INSERT y hay que tomar los
     * atributos completos o el cambio no quedaría registrado.
     *
     * @return array<string, mixed>
     */
    private function cambiosDe(Model $configuracion): array
    {
        return $configuracion->wasRecentlyCreated
            ? $configuracion->getAttributes()
            : $configuracion->getChanges();
    }

    private function usuarioAutenticado(): ?User
    {
        $usuario = auth()->user();

        return $usuario instanceof User ? $usuario : null;
    }
}
