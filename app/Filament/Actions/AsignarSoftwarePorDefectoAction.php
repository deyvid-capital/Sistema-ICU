<?php

namespace App\Filament\Actions;

use App\Models\Area;
use App\Models\Software;
use App\Models\SoftwareUsuario;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Accion reutilizable para asignar software "por defecto" en bloque.
 *
 * Se comporta segun el dueño del gestor de relaciones:
 * - Si el dueño es un Software: asigna ese software a todos los usuarios de un área.
 * - Si el dueño es un User: asigna al usuario todos los software de una categoría.
 */
class AsignarSoftwarePorDefectoAction
{
    public static function make(RelationManager $relationManager): Action
    {
        $owner = $relationManager->getOwnerRecord();
        $esUsuario = $owner instanceof User;

        return Action::make('asignarPorDefecto')
            ->label('Asignar por defecto')
            ->icon(Heroicon::UserPlus)
            ->color('success')
            ->modalHeading($esUsuario
                ? 'Asignar software por categoría'
                : 'Asignar este software por área')
            ->modalSubmitActionLabel('Asignar')
            ->schema([
                $esUsuario
                    ? Select::make('categoria')
                        ->label('Categoría de software')
                        ->options(fn (): array => Software::query()
                            ->whereNotNull('categoria')
                            ->distinct()
                            ->orderBy('categoria')
                            ->pluck('categoria', 'categoria')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required()
                    : Select::make('id_area')
                        ->label('Área')
                        ->options(fn (): array => Area::query()
                            ->orderBy('area')
                            ->pluck('area', 'id_area')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
            ])
            ->action(function (array $data) use ($owner, $esUsuario): void {
                $asignados = $esUsuario
                    ? self::asignarSoftwarePorCategoria($owner, $data['categoria'])
                    : self::asignarUsuariosPorArea($owner, (int) $data['id_area']);

                Notification::make()
                    ->success()
                    ->title('Asignación por defecto completada')
                    ->body($esUsuario
                        ? "Se asignaron {$asignados} software(s) nuevos al usuario."
                        : "Se asignaron {$asignados} usuario(s) nuevos a este software.")
                    ->send();
            });
    }

    /**
     * Asigna todos los software de una categoría al usuario dueño.
     */
    private static function asignarSoftwarePorCategoria(Model $usuario, string $categoria): int
    {
        $asignados = 0;

        Software::query()
            ->where('categoria', $categoria)
            ->each(function (Software $software) use ($usuario, &$asignados): void {
                $registro = SoftwareUsuario::firstOrCreate(
                    [
                        'usuario_id' => $usuario->getKey(),
                        'id_software' => $software->getKey(),
                    ],
                    [
                        'estado' => true,
                    ],
                );

                if ($registro->wasRecentlyCreated) {
                    $asignados++;
                }
            });

        return $asignados;
    }

    /**
     * Asigna el software dueño a todos los usuarios de un área.
     */
    private static function asignarUsuariosPorArea(Model $software, int $areaId): int
    {
        $asignados = 0;

        User::query()
            ->where('id_area', $areaId)
            ->each(function (User $usuario) use ($software, &$asignados): void {
                $registro = SoftwareUsuario::firstOrCreate(
                    [
                        'usuario_id' => $usuario->getKey(),
                        'id_software' => $software->getKey(),
                    ],
                    [
                        'estado' => true,
                    ],
                );

                if ($registro->wasRecentlyCreated) {
                    $asignados++;
                }
            });

        return $asignados;
    }
}
