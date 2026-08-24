<?php

namespace App\Providers;

use App\Filament\Pages\Auth\TwoFactorChallenge;
use App\Listeners\ResetearIntentosAlIniciarSesion;
use App\Livewire\Profile\TwoFactorAuthentication;
use App\Models\AsigCelular;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\Movimiento;
use App\Models\PdfEquipo;
use App\Models\SoftwareExc;
use App\Observers\AsigCelularObserver;
use App\Observers\AsignacionCargadorObserver;
use App\Observers\AsignacionEquipoObserver;
use App\Observers\BreezySessionObserver;
use App\Observers\CargadorObserver;
use App\Observers\EquipoObserver;
use App\Observers\MovimientoObserver;
use App\Observers\PdfEquipoObserver;
use App\Observers\SoftwareExcObserver;
use App\Policies\ActivityPolicy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Jeffgreco13\FilamentBreezy\Models\BreezySession;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

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
        FilamentTimezone::set(config('app.display_timezone'));

        Gate::policy(Activity::class, ActivityPolicy::class);

        BreezySession::observe(BreezySessionObserver::class);
        AsignacionEquipo::observe(AsignacionEquipoObserver::class);
        AsignacionCargador::observe(AsignacionCargadorObserver::class);
        AsigCelular::observe(AsigCelularObserver::class);
        Cargador::observe(CargadorObserver::class);
        Equipo::observe(EquipoObserver::class);
        SoftwareExc::observe(SoftwareExcObserver::class);
        PdfEquipo::observe(PdfEquipoObserver::class);
        Movimiento::observe(MovimientoObserver::class);

        Livewire::component('two-factor-page', TwoFactorChallenge::class);
        Livewire::component('two_factor_authentication', TwoFactorAuthentication::class);

        Event::listen(Login::class, ResetearIntentosAlIniciarSesion::class);

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

        Password::defaults(function (): Password {
            return Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->when(
                    ! app()->environment('testing'),
                    fn (Password $rule): Password => $rule->uncompromised(3),
                );
        });
    }
}
