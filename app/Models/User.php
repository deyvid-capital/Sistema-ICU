<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Concerns\CascadesDependentSoftDeletes;
use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use App\Support\Sessions\UserSessions;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Livewire\Livewire;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['id', 'name', 'nombres', 'apellidos', 'carnet', 'id_area', 'id_cargo', 'email', 'telefono', 'password', 'email_verified_at', 'acceso_habilitado', 'acceso_expira_at', 'requiere_2fa'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use CascadesDependentSoftDeletes,
        CausesActivity,
        HasFactory,
        HasRoles,
        HasUuids,
        LogsIcuActivity,
        Notifiable,
        PreventsForceDeleteWithDependents,
        SoftDeletes,
        TwoFactorAuthenticatable;

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'acceso_habilitado' => true,
        'requiere_2fa' => false,
        'intentos_fallidos' => 0,
    ];

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if (! $user->isDirty('email')) {
                return;
            }

            $component = Livewire::current();

            if ($component && $component::class === 'App\\Livewire\\Profile\\PersonalInfo') {
                $user->email = $user->getOriginal('email');
            }
        });
    }

    /**
     * @var array<int, string>
     */
    protected static array $forceDeleteGuardedRelations = [
        'movimientos',
        'accesosSharepoint',
        'accesosSistemas',
        'accesosDaza',
        'accesosInternet',
        'softwareUsuarios',
        'softwareExcepciones',
        'licenciasUsuarios',
        'azureAd',
        'unidadesCompartidas',
        'asignacionesEquipos',
        'asignacionesCelulares',
        'firmantes',
    ];

    /**
     * @return array<string>
     */
    protected function dependentSoftDeleteRelations(): array
    {
        return [
            'movimientos',
            'accesosSharepoint',
            'accesosSistemas',
            'accesosDaza',
            'accesosInternet',
            'softwareUsuarios',
            'softwareExcepciones',
            'licenciasUsuarios',
            'azureAd',
            'unidadesCompartidas',
            'asignacionesEquipos',
            'asignacionesCelulares',
        ];
    }

    /**
     * Determina si el usuario puede entrar al panel.
     *
     * Requiere: acceso habilitado y vigente, no estar bloqueado por intentos
     * fallidos, tener al menos un rol asignado y no estar eliminado.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->motivoAccesoDenegado() === null;
    }

    /**
     * Motivo por el que el usuario no puede entrar al panel, o null si puede.
     * Se usa tanto para gatear el acceso como para mostrar un mensaje claro en el login.
     */
    public function motivoAccesoDenegado(): ?string
    {
        if ($this->trashed()) {
            return 'Tu usuario fue eliminado. Contacta a un administrador.';
        }

        if (! $this->acceso_habilitado) {
            return 'Tu acceso al sistema está deshabilitado. Contacta a un administrador.';
        }

        if ($this->acceso_expira_at !== null && ! $this->acceso_expira_at->isFuture()) {
            return 'Tu acceso venció el '.$this->acceso_expira_at->format('d/m/Y H:i').'. Contacta a un administrador.';
        }

        if ($this->estaBloqueado()) {
            return 'Tu cuenta está bloqueada por intentos fallidos. Contacta a un administrador.';
        }

        if (! $this->roles()->exists()) {
            return 'Tu usuario no tiene un rol asignado. Contacta a un administrador.';
        }

        return null;
    }

    /**
     * El acceso está habilitado y, si tiene vencimiento, aún no expira.
     * (No contempla bloqueo ni rol; eso lo evalúa canAccessPanel).
     */
    public function tieneAccesoVigente(): bool
    {
        return $this->acceso_habilitado
            && ($this->acceso_expira_at === null || $this->acceso_expira_at->isFuture());
    }

    /**
     * El usuario está bloqueado por intentos de inicio de sesión fallidos.
     */
    public function estaBloqueado(): bool
    {
        return $this->bloqueado_at !== null;
    }

    /**
     * Registra un intento de inicio de sesión fallido; al alcanzar el máximo
     * configurado, bloquea la cuenta y cierra sus sesiones activas.
     */
    public function registrarIntentoFallido(): void
    {
        $this->increment('intentos_fallidos');

        if ($this->intentos_fallidos >= (int) config('acceso.max_intentos_fallidos', 5) && ! $this->estaBloqueado()) {
            $this->forceFill(['bloqueado_at' => now()])->save();
            UserSessions::terminate($this);
        }
    }

    /**
     * Desbloqueo manual por un administrador.
     */
    public function desbloquear(): void
    {
        $this->forceFill([
            'intentos_fallidos' => 0,
            'bloqueado_at' => null,
        ])->save();
    }

    /**
     * Reinicia el contador tras un inicio de sesión exitoso.
     */
    public function resetearIntentosFallidos(): void
    {
        if ($this->intentos_fallidos === 0 && $this->bloqueado_at === null) {
            return;
        }

        $this->forceFill([
            'intentos_fallidos' => 0,
            'bloqueado_at' => null,
        ])->save();
    }

    /**
     * Get the area that the user belongs to.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'id_area', 'id_area');
    }

    /**
     * Get the position that the user belongs to.
     */
    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo', 'id_cargo');
    }

    /**
     * Get the movements for the user.
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class, 'usuario_id');
    }

    /**
     * Get the user's most recent movement.
     */
    public function ultimoMovimiento(): HasOne
    {
        return $this->hasOne(Movimiento::class, 'usuario_id')->latestOfMany('fecha');
    }

    /**
     * Indica si el usuario está de Alta según su último movimiento.
     * true = Alta, false = Baja o sin movimientos.
     */
    public function estaDeAlta(): bool
    {
        return $this->ultimoMovimiento?->tipo === 'Alta';
    }

    /**
     * Get the SharePoint accesses for the user.
     */
    public function accesosSharepoint(): HasMany
    {
        return $this->hasMany(AccesoSharepoint::class, 'usuario_id');
    }

    /**
     * Get the system accesses for the user.
     */
    public function accesosSistemas(): HasMany
    {
        return $this->hasMany(AccesoSistema::class, 'usuario_id');
    }

    /**
     * Get the DAZA accesses for the user.
     */
    public function accesosDaza(): HasMany
    {
        return $this->hasMany(AccesoDaza::class, 'usuario_id');
    }

    /**
     * Get the internet accesses for the user.
     */
    public function accesosInternet(): HasMany
    {
        return $this->hasMany(AccesoInternet::class, 'usuario_id');
    }

    /**
     * Get the software assignments for the user.
     */
    public function softwareUsuarios(): HasMany
    {
        return $this->hasMany(SoftwareUsuario::class, 'usuario_id');
    }

    /**
     * Get the software exceptions for the user.
     */
    public function softwareExcepciones(): HasMany
    {
        return $this->hasMany(SoftwareExc::class, 'usuario_id');
    }

    /**
     * Get the license assignments for the user.
     */
    public function licenciasUsuarios(): HasMany
    {
        return $this->hasMany(LicenciaUsuario::class, 'usuario_id');
    }

    /**
     * The licenses that belong to the user.
     */
    public function licencias(): BelongsToMany
    {
        return $this->belongsToMany(
            Licencia::class,
            'licencias_usuarios',
            'usuario_id',
            'licencia_id',
            'id',
            'id'
        )->withPivot(['fecha', 'estado'])->withTimestamps();
    }

    /**
     * Get the Azure AD records for the user.
     */
    public function azureAd(): HasMany
    {
        return $this->hasMany(AzureAd::class, 'usuario_id');
    }

    /**
     * Get the shared folder accesses for the user.
     */
    public function unidadesCompartidas(): HasMany
    {
        return $this->hasMany(AccUdCompartida::class, 'usuario_id');
    }

    /**
     * Get the equipment assignments for the user.
     */
    public function asignacionesEquipos(): HasMany
    {
        return $this->hasMany(AsignacionEquipo::class, 'usuario_id');
    }

    /**
     * Get the phone assignments for the user.
     */
    public function asignacionesCelulares(): HasMany
    {
        return $this->hasMany(AsigCelular::class, 'usuario_id');
    }

    /**
     * Get the signatures for the user.
     */
    public function firmantes(): HasMany
    {
        return $this->hasMany(Firmante::class, 'usuario_id');
    }

    /**
     * Preferencias de notificación (por tipo de evento y canal).
     */
    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class, 'usuario_id');
    }

    /**
     * Indica si el usuario debe recibir un tipo de evento por un canal dado.
     * Por defecto true (opt-out): sólo una preferencia explícita lo desactiva.
     */
    public function debeRecibir(string $tipoEvento, string $canal): bool
    {
        if (! $this->exists) {
            return true;
        }

        return $this->notificationPreferences()
            ->where('tipo_evento', $tipoEvento)
            ->where('canal', $canal)
            ->value('habilitado') ?? true;
    }

    /**
     * Activa TOTP sin generar ni guardar códigos de recuperación.
     */
    public function enableTwoFactorAuthentication(): void
    {
        $twoFactorData = [
            'two_factor_secret' => filament('filament-breezy')->getEngine()->generateSecretKey(),
            'two_factor_recovery_codes' => null,
        ];

        if ($this->breezySession) {
            $this->disableTwoFactorAuthentication();
        }

        $this->breezySessions()->create($twoFactorData);
        $this->load(['breezySessions', 'breezySession']);
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(): array
    {
        return [];
    }

    public function reGenerateRecoveryCodes(): void
    {
        $this->breezySession?->forceFill([
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    public function destroyRecoveryCode(string $recoveryCode): void
    {
        $this->breezySession?->forceFill([
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'acceso_habilitado' => 'boolean',
            'requiere_2fa' => 'boolean',
            'acceso_expira_at' => 'datetime',
            'bloqueado_at' => 'datetime',
        ];
    }
}
