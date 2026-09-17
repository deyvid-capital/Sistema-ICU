<?php

namespace App\Models;

use App\Enums\EstadoAprobacionExcepcion;
use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\LogsIcuActivity;
use App\Support\Seguridad\ProteccionSuperAdmin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareExc extends Model
{
    use BelongsToUsuario, GuardsAgainstTrashedRelations, HasFactory, LogsIcuActivity, SoftDeletes;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $table = 'software_excepciones';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'estado_aprobacion' => 'pendiente',
    ];

    protected $fillable = [
        'usuario_id',
        'software_id',
        'area_aprobadora_id',
        'aprobador_id',
        'tipo_excepcion',
        'motivo',
        'fecha_inicio',
        'fecha_fin',
        'estado_aprobacion',
        'decidido_por_id',
        'decidido_at',
        'comentario_decision',
    ];

    protected static function booted(): void
    {
        static::updating(function (SoftwareExc $excepcion): void {
            if ($excepcion->estabaPendienteAlLeer()) {
                return;
            }

            if ($excepcion->isDirty(['area_aprobadora_id', 'aprobador_id'])) {
                $excepcion->area_aprobadora_id = $excepcion->getOriginal('area_aprobadora_id');
                $excepcion->aprobador_id = $excepcion->getOriginal('aprobador_id');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'decidido_at' => 'datetime',
            'estado_aprobacion' => EstadoAprobacionExcepcion::class,
        ];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'software_id', 'id_software');
    }

    public function areaAprobadora(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_aprobadora_id', 'id_area');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobador_id');
    }

    public function decididoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por_id');
    }

    public function estaPendiente(): bool
    {
        return $this->estado_aprobacion?->estaPendiente() ?? true;
    }

    public function puedeDecidir(?User $actor): bool
    {
        if ($actor === null || $this->trashed() || ! $this->estaPendiente()) {
            return false;
        }

        if ($actor->getKey() === $this->aprobador_id) {
            return true;
        }

        return $actor->hasRole(ProteccionSuperAdmin::nombreRol());
    }

    public function requiereNotaDeIntervencion(?User $actor): bool
    {
        if ($actor === null || $actor->getKey() === $this->aprobador_id) {
            return false;
        }

        return $actor->hasRole(ProteccionSuperAdmin::nombreRol());
    }

    /**
     * @param  Builder<SoftwareExc>  $query
     * @return Builder<SoftwareExc>
     */
    public function scopePendientesDe(Builder $query, User $user): Builder
    {
        return $query
            ->where('aprobador_id', $user->getKey())
            ->where('estado_aprobacion', EstadoAprobacionExcepcion::Pendiente);
    }

    private function estabaPendienteAlLeer(): bool
    {
        $original = $this->getOriginal('estado_aprobacion');

        if ($original instanceof EstadoAprobacionExcepcion) {
            return $original->estaPendiente();
        }

        return $original === EstadoAprobacionExcepcion::Pendiente->value || $original === null;
    }
}
