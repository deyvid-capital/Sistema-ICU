<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['id', 'name', 'carnet', 'id_area', 'id_cargo', 'email', 'telefono', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
