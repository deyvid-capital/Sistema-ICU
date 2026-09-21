<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use App\Services\Licencias\GestionarCupoLicencia;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Licencia extends Model
{
    use HasFactory;
    use LogsIcuActivity;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['licenciasUsuarios'];

    protected $fillable = [
        'nombre_licencia',
        'categoria',
        'descripcion',
        'estado',
        'total_licencias',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'total_licencias' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Licencia $licencia): void {
            if (! $licencia->isDirty('total_licencias')) {
                return;
            }

            try {
                app(GestionarCupoLicencia::class)->assertPuedeReducirTotal(
                    $licencia,
                    (int) $licencia->total_licencias,
                );
            } catch (InvalidArgumentException $exception) {
                Notification::make()
                    ->danger()
                    ->title('No se puede reducir el total')
                    ->body($exception->getMessage())
                    ->persistent()
                    ->send();

                throw new Halt;
            }
        });
    }

    /**
     * Get the user assignments for the license.
     */
    public function licenciasUsuarios(): HasMany
    {
        return $this->hasMany(LicenciaUsuario::class, 'licencia_id');
    }

    /**
     * Active (non-trashed) assignments that consume a seat.
     */
    public function licenciasUsuariosActivas(): HasMany
    {
        return $this->licenciasUsuarios()->where('estado', true);
    }

    /**
     * The users that belong to the license.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'licencias_usuarios',
            'licencia_id',
            'usuario_id',
            'id',
            'id'
        )->withPivot(['fecha', 'estado'])->withTimestamps();
    }

    public function contarAsignadas(?LicenciaUsuario $ignorar = null): int
    {
        $query = $this->licenciasUsuariosActivas();

        if ($ignorar?->exists) {
            $query->whereKeyNot($ignorar->getKey());
        }

        return $query->count();
    }

    public function tieneCupoDisponible(?LicenciaUsuario $ignorar = null): bool
    {
        return ((int) $this->total_licencias - $this->contarAsignadas($ignorar)) > 0;
    }

    public function etiquetaConCupo(): string
    {
        return "{$this->nombre_licencia} ({$this->asignadas}/{$this->total_licencias})";
    }

    public function getAsignadasAttribute(): int
    {
        if (array_key_exists('licencias_usuarios_activas_count', $this->attributes)) {
            return (int) $this->attributes['licencias_usuarios_activas_count'];
        }

        return $this->contarAsignadas();
    }

    public function getDisponiblesAttribute(): int
    {
        return max((int) $this->total_licencias - $this->asignadas, 0);
    }
}
