<?php

namespace App\Services\Software;

use App\Models\Cargo;
use App\Models\CategoriaSoftware;
use App\Models\Software;
use App\Models\SoftwareCargo;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Asigna software a usuarios en bloque: por categoría, por el cargo del
 * usuario o un software a todas las personas de un cargo.
 *
 * - Solo se asigna software activo.
 * - Una asignación que ya existe y está activa no se toca.
 * - Una asignación eliminada o inactiva se restaura y se activa, en lugar de
 *   chocar con el índice único (usuario, software).
 *
 * Cada método devuelve cuántas asignaciones quedaron activas gracias a él.
 */
class AsignarSoftwareEnBloque
{
    /**
     * Todo el software activo de la categoría, al usuario.
     */
    public function porCategoria(User $usuario, CategoriaSoftware $categoria): int
    {
        $softwareIds = $categoria->software()
            ->where('estado', true)
            ->pluck('id_software');

        return $this->asignarAlUsuario($usuario, $softwareIds->all());
    }

    /**
     * Todo el software activo autorizado para el cargo del usuario.
     */
    public function porCargoDelUsuario(User $usuario): int
    {
        if ($usuario->id_cargo === null) {
            return 0;
        }

        $softwareIds = SoftwareCargo::query()
            ->where('cargo_id', $usuario->id_cargo)
            ->whereHas('software', fn (Builder $query): Builder => $query->where('estado', true))
            ->pluck('software_id');

        return $this->asignarAlUsuario($usuario, $softwareIds->all());
    }

    /**
     * El software, a todas las personas del cargo que no están de baja.
     */
    public function aPersonasDelCargo(Software $software, Cargo $cargo): int
    {
        if (! $software->estado) {
            return 0;
        }

        return DB::transaction(function () use ($software, $cargo): int {
            $asignados = 0;

            User::query()
                ->where('id_cargo', $cargo->getKey())
                ->whereDoesntHave('ultimoMovimiento', fn (Builder $query): Builder => $query->where('tipo', 'Baja'))
                ->each(function (User $usuario) use ($software, &$asignados): void {
                    if ($this->asignar((string) $usuario->getKey(), (int) $software->getKey())) {
                        $asignados++;
                    }
                });

            return $asignados;
        });
    }

    /**
     * @param  list<int|string>  $softwareIds
     */
    private function asignarAlUsuario(User $usuario, array $softwareIds): int
    {
        return DB::transaction(function () use ($usuario, $softwareIds): int {
            $asignados = 0;

            foreach (array_unique($softwareIds) as $softwareId) {
                if ($this->asignar((string) $usuario->getKey(), (int) $softwareId)) {
                    $asignados++;
                }
            }

            return $asignados;
        });
    }

    /**
     * Crea, restaura o activa la asignación. Devuelve false si ya estaba activa.
     */
    private function asignar(string $usuarioId, int $softwareId): bool
    {
        $registro = SoftwareUsuario::withTrashed()
            ->where('usuario_id', $usuarioId)
            ->where('id_software', $softwareId)
            ->first();

        if ($registro === null) {
            SoftwareUsuario::query()->create([
                'usuario_id' => $usuarioId,
                'id_software' => $softwareId,
                'estado' => true,
            ]);

            return true;
        }

        if (! $registro->trashed() && $registro->estado) {
            return false;
        }

        if ($registro->trashed()) {
            $registro->restore();
        }

        $registro->update(['estado' => true]);

        return true;
    }
}
