<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\UsuariosPanel\AccesosTab;
use App\Filament\Widgets\UsuariosPanel\LicenciasTab;
use App\Filament\Widgets\UsuariosPanel\PestanaUsuarios;
use App\Filament\Widgets\UsuariosPanel\SoftwareTab;
use App\Filament\Widgets\UsuariosPanel\UsuariosTab;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\Widget;

/**
 * Panel de usuarios con pestañas: cada pestaña es una tabla propia
 * (subclase de PestanaUsuarios) y solo se monta la que está activa.
 *
 * Para agregar una vista nueva basta crear la clase y sumarla a PESTANAS.
 * Un único permiso de Shield (View:UsuariosPanelWidget) cubre todas.
 */
class UsuariosPanelWidget extends Widget
{
    use HasWidgetShield;

    /**
     * @var array<string, class-string<PestanaUsuarios>>
     */
    public const PESTANAS = [
        'usuarios' => UsuariosTab::class,
        'licencias' => LicenciasTab::class,
        'software' => SoftwareTab::class,
        'accesos' => AccesosTab::class,
    ];

    protected static ?int $sort = 8;

    protected string $view = 'filament.widgets.usuarios-panel-widget';

    protected int|string|array $columnSpan = 'full';

    public string $pestana = 'usuarios';

    /**
     * El valor llega del navegador: cualquier clave desconocida vuelve a la primera pestaña.
     */
    public function pestanaActiva(): string
    {
        return array_key_exists($this->pestana, self::PESTANAS) ? $this->pestana : array_key_first(self::PESTANAS);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $activa = $this->pestanaActiva();

        return [
            'activa' => $activa,
            'componente' => self::PESTANAS[$activa],
            'pestanas' => self::PESTANAS,
        ];
    }
}
