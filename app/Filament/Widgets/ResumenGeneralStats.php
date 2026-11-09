<?php

namespace App\Filament\Widgets;

use App\Enums\EstadoCertificadoFirma;
use App\Enums\EstadoEquipo;
use App\Models\CertificadoFirma;
use App\Models\Equipo;
use App\Models\Licencia;
use App\Models\LicenciaUsuario;
use App\Models\Software;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

/**
 * Fila de KPIs del dashboard: equipos, licencias, software, usuarios y firmas.
 */
class ResumenGeneralStats extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected ?string $heading = 'Resumen general';

    protected ?string $pollingInterval = null;

    protected function getColumns(): int|array|null
    {
        return ['default' => 1, 'md' => 2, 'xl' => 3];
    }

    /**
     * @return list<Stat>
     */
    protected function getStats(): array
    {
        return [
            ...$this->statsDeEquipos(),
            $this->statDeLicencias(),
            $this->statDeSoftware(),
            $this->statDeUsuarios(),
            $this->statDeFirmas(),
        ];
    }

    /**
     * @return list<Stat>
     */
    private function statsDeEquipos(): array
    {
        $porEstado = Equipo::query()
            ->toBase()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $total = (int) $porEstado->sum();
        $asignados = (int) ($porEstado[EstadoEquipo::Asignado->value] ?? 0);
        $disponibles = (int) ($porEstado[EstadoEquipo::SinAsignar->value] ?? 0);

        return [
            Stat::make('Equipos asignados', Number::format($asignados))
                ->description("{$this->porcentaje($asignados, $total)} de {$total} equipos")
                ->descriptionIcon(Heroicon::UserGroup)
                ->color('success'),
            Stat::make('Equipos disponibles', Number::format($disponibles))
                ->description("{$this->porcentaje($disponibles, $total)} listos para asignar")
                ->descriptionIcon(Heroicon::ComputerDesktop)
                ->color($disponibles > 0 ? 'info' : 'warning'),
        ];
    }

    private function statDeLicencias(): Stat
    {
        $cupos = (int) Licencia::query()->where('estado', true)->sum('total_licencias');

        $asignadas = LicenciaUsuario::query()
            ->where('estado', true)
            ->whereHas('licencia', fn (Builder $query) => $query->where('estado', true))
            ->count();

        $libres = max($cupos - $asignadas, 0);

        return Stat::make('Total de licencias', Number::format($cupos))
            ->description("{$asignadas} asignadas · {$libres} libres")
            ->descriptionIcon(Heroicon::Key)
            ->color($asignadas > $cupos ? 'danger' : 'primary');
    }

    private function statDeSoftware(): Stat
    {
        $total = Software::query()->count();
        $autorizados = Software::query()->where('estado', true)->count();

        return Stat::make('Total de software', Number::format($total))
            ->description("{$autorizados} autorizados")
            ->descriptionIcon(Heroicon::CodeBracketSquare)
            ->color('primary');
    }

    /**
     * Alta o baja según el último movimiento de cada usuario (User::estaDeAlta()).
     */
    private function statDeUsuarios(): Stat
    {
        $total = User::query()->count();
        $deAlta = $this->usuariosConUltimoMovimiento('Alta');
        $deBaja = $this->usuariosConUltimoMovimiento('Baja');

        return Stat::make('Usuarios', Number::format($total))
            ->description("{$deAlta} de alta · {$deBaja} de baja")
            ->descriptionIcon(Heroicon::Users)
            ->color('primary');
    }

    private function statDeFirmas(): Stat
    {
        $activas = CertificadoFirma::query()->where('estado', EstadoCertificadoFirma::Activo)->count();

        $porVencer = CertificadoFirma::query()
            ->where('estado', EstadoCertificadoFirma::Activo)
            ->whereBetween('vigente_hasta', [now(), now()->addDays(30)])
            ->count();

        return Stat::make('Firmas digitales activas', Number::format($activas))
            ->description($porVencer > 0 ? "{$porVencer} vencen en 30 días" : 'Ninguna vence en 30 días')
            ->descriptionIcon($porVencer > 0 ? Heroicon::ExclamationTriangle : Heroicon::ShieldCheck)
            ->color($porVencer > 0 ? 'warning' : 'success');
    }

    private function usuariosConUltimoMovimiento(string $tipo): int
    {
        return User::query()
            ->whereHas('ultimoMovimiento', fn (Builder $query) => $query->where('tipo', $tipo))
            ->count();
    }

    private function porcentaje(int $parte, int $total): string
    {
        return $total > 0 ? Number::percentage($parte / $total * 100) : '0%';
    }
}
