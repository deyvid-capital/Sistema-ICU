<?php

namespace App\Providers;

use App\Models\AsigCelular;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\PdfEquipo;
use App\Models\SoftwareExc;
use App\Observers\AsigCelularObserver;
use App\Observers\AsignacionCargadorObserver;
use App\Observers\AsignacionEquipoObserver;
use App\Observers\CargadorObserver;
use App\Observers\EquipoObserver;
use App\Observers\PdfEquipoObserver;
use App\Observers\SoftwareExcObserver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        AsignacionEquipo::observe(AsignacionEquipoObserver::class);
        AsignacionCargador::observe(AsignacionCargadorObserver::class);
        AsigCelular::observe(AsigCelularObserver::class);
        Cargador::observe(CargadorObserver::class);
        Equipo::observe(EquipoObserver::class);
        SoftwareExc::observe(SoftwareExcObserver::class);
        PdfEquipo::observe(PdfEquipoObserver::class);
        DatePicker::configureUsing(function (DatePicker $component): void {
            $component
                ->native(false)
                ->locale('es')
                ->displayFormat('d/m/Y');
        });

        DateTimePicker::configureUsing(function (DateTimePicker $component): void {
            $component
                ->native(false)
                ->locale('es')
                ->displayFormat('d/m/Y H:i');
        });
        
    }
}
