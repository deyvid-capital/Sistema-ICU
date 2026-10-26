<?php

namespace App\Models;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Models\Concerns\LogsIcuActivity;
use Database\Factories\SolicitudFirmaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Envío a firmar: uno o varios documentos y uno o varios firmantes.
 */
class SolicitudFirma extends Model
{
    /** @use HasFactory<SolicitudFirmaFactory> */
    use HasFactory;

    use LogsIcuActivity;
    use SoftDeletes;

    protected $table = 'solicitudes_firma';

    protected $fillable = [
        'codigo',
        'asunto',
        'mensaje',
        'orden_obligatorio',
        'estado',
        'fecha_limite',
        'recordatorio_cada_horas',
        'creado_por',
        'enviada_at',
        'finalizada_at',
        'cancelada_at',
        'cancelada_por',
        'motivo_cancelacion',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoSolicitudFirma::class,
            'orden_obligatorio' => 'boolean',
            'fecha_limite' => 'date',
            'recordatorio_cada_horas' => 'integer',
            'enviada_at' => 'datetime',
            'finalizada_at' => 'datetime',
            'cancelada_at' => 'datetime',
        ];
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por')->withTrashed();
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(SolicitudFirmaDocumento::class, 'solicitud_firma_id');
    }

    public function firmantes(): HasMany
    {
        return $this->hasMany(SolicitudFirmaFirmante::class, 'solicitud_firma_id')->orderBy('orden');
    }

    /**
     * Envíos que el usuario creó o en los que debe firmar.
     *
     * @param  Builder<self>  $query
     */
    public function scopeDeUsuario(Builder $query, User $usuario): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('creado_por', $usuario->getKey())
            ->orWhereHas('firmantes', fn (Builder $query) => $query->where('usuario_id', $usuario->getKey())));
    }

    /**
     * Recuadros que el usuario todavía tiene que firmar en documentos en curso.
     *
     * @return Builder<SolicitudFirmaFirma>
     */
    public function firmasPendientesDe(User $usuario): Builder
    {
        return SolicitudFirmaFirma::query()
            ->where('estado', EstadoFirma::Pendiente)
            ->whereHas('documento', fn (Builder $query) => $query
                ->where('solicitud_firma_id', $this->id)
                ->where('estado', EstadoDocumentoFirma::EnCurso))
            ->whereHas('firmante', fn (Builder $query) => $query->where('usuario_id', $usuario->getKey()))
            ->with(['documento.solicitud', 'firmante']);
    }

    /**
     * Finalizada cuando todos sus documentos quedaron completados o rechazados.
     */
    public function finalizarSiCorresponde(): bool
    {
        if (! $this->estaEnCurso() || $this->documentos()->where('estado', EstadoDocumentoFirma::EnCurso)->exists()) {
            return false;
        }

        $this->update(['estado' => EstadoSolicitudFirma::Finalizada, 'finalizada_at' => now()]);

        return true;
    }

    public function estaEnCurso(): bool
    {
        return $this->estado === EstadoSolicitudFirma::EnCurso;
    }

    public function esCreadaPor(User $usuario): bool
    {
        return $this->creado_por === $usuario->getKey();
    }

    public function tieneFirmante(User $usuario): bool
    {
        return $this->firmantes()->where('usuario_id', $usuario->getKey())->exists();
    }

    /**
     * Carpeta del envío en el disco privado.
     */
    public function directorio(): string
    {
        return 'firma-digital/solicitudes/'.$this->codigo;
    }
}
