<?php

namespace App\Models\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

/**
 * Avisa —sin bloquear— cuando una asignación apunta a uno o más registros de
 * catálogo que están en estado no activo. La asignación se guarda igual; el
 * aviso es informativo para quien la registra.
 *
 * Los modelos que la usan deben declarar:
 * protected static array $inactiveWarningRelations = [
 *     'software' => ['label' => 'el software', 'attribute' => 'nombre_software'],
 * ];
 */
trait WarnsOnInactiveCatalogRelations
{
    protected static function bootWarnsOnInactiveCatalogRelations(): void
    {
        static::saved(function (self $model): void {
            $model->notifyInactiveCatalogRelations();
        });
    }

    protected function notifyInactiveCatalogRelations(): void
    {
        $inactivos = [];

        foreach (static::$inactiveWarningRelations as $relation => $config) {
            $related = $this->{$relation};

            if (! $related instanceof Model) {
                continue;
            }

            if ((bool) $related->estado) {
                continue;
            }

            $inactivos[] = "{$config['label']} «{$related->{$config['attribute']}}»";
        }

        if ($inactivos === []) {
            return;
        }

        Notification::make()
            ->title('Recurso en estado no activo')
            ->body($this->buildInactiveCatalogWarningBody($inactivos))
            ->warning()
            ->duration(12000)
            ->send();
    }

    /**
     * @param  array<int, string>  $inactivos
     */
    protected function buildInactiveCatalogWarningBody(array $inactivos): string
    {
        $listado = count($inactivos) === 1
            ? "{$inactivos[0]} está"
            : implode(' y ', $inactivos).' están';

        return "La asignación se guardó, pero {$listado} en estado no activo.";
    }
}
