<?php

namespace App\Providers;

use App\Filament\Pages\Auth\TwoFactorChallenge;
use App\Livewire\Profile\TwoFactorAuthentication;
use App\Media\IcuPathGenerator;
use App\Models\AppearanceSetting;
use App\Models\AsigCelular;
use App\Models\AsignacionCargador;
use App\Models\AsignacionEquipo;
use App\Models\Cargador;
use App\Models\Equipo;
use App\Models\MailSetting;
use App\Models\MediaSetting;
use App\Models\Movimiento;
use App\Models\PdfEquipo;
use App\Models\SoftwareExc;
use App\Models\User;
use App\Observers\AppearanceSettingObserver;
use App\Observers\AsigCelularObserver;
use App\Observers\AsignacionCargadorObserver;
use App\Observers\AsignacionEquipoObserver;
use App\Observers\BreezySessionObserver;
use App\Observers\CargadorObserver;
use App\Observers\EquipoObserver;
use App\Observers\MailSettingObserver;
use App\Observers\MediaSettingObserver;
use App\Observers\MovimientoObserver;
use App\Observers\PdfEquipoObserver;
use App\Observers\SoftwareExcObserver;
use App\Observers\UserObserver;
use App\Policies\ActivityPolicy;
use App\Services\Archivos\ArchivosService;
use App\Services\Configuracion\MailConfigService;
use App\Services\Configuracion\TemaService;
use App\Support\Auditoria\AuditorConfiguracion;
use App\Support\Auditoria\AuditorSeguridad;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Support\Facades\FilamentTimezone;
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
        $this->app->singleton(TemaService::class);
        $this->app->singleton(ArchivosService::class);
        $this->app->singleton(MailConfigService::class);
        $this->app->singleton(AuditorSeguridad::class);
        $this->app->singleton(AuditorConfiguracion::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentTimezone::set(config('app.display_timezone'));

        config([
            'media-library.path_generator' => IcuPathGenerator::class,
            'media-library.disk_name' => config('archivos.disco', env('ARCHIVOS_DISK', 'public')),
        ]);

        Gate::policy(Activity::class, ActivityPolicy::class);

        AppearanceSetting::observe(AppearanceSettingObserver::class);
        MediaSetting::observe(MediaSettingObserver::class);
        MailSetting::observe(MailSettingObserver::class);
        BreezySession::observe(BreezySessionObserver::class);
        AsignacionEquipo::observe(AsignacionEquipoObserver::class);
        AsignacionCargador::observe(AsignacionCargadorObserver::class);
        AsigCelular::observe(AsigCelularObserver::class);
        Cargador::observe(CargadorObserver::class);
        Equipo::observe(EquipoObserver::class);
        SoftwareExc::observe(SoftwareExcObserver::class);
        PdfEquipo::observe(PdfEquipoObserver::class);
        Movimiento::observe(MovimientoObserver::class);
        User::observe(UserObserver::class);

        $this->app->booted(function (): void {
            app(MailConfigService::class)->aplicar();
        });

        Livewire::component('two-factor-page', TwoFactorChallenge::class);
        Livewire::component('two_factor_authentication', TwoFactorAuthentication::class);

        // Los listeners de app/Listeners los descubre Laravel por el tipo del
        // argumento de handle(). Registrarlos aquí además los duplicaría.

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
