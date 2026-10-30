<?php

namespace App\Models;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Models\Concerns\TieneEstadoFirma;
use Database\Factories\SolicitudFirmaDocumentoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un PDF de un envío. Se firma una copia propia (snapshot) guardada en el disco
 * privado, nunca el archivo original del sistema.
 */
class SolicitudFirmaDocumento extends Model
{
    /** @use HasFactory<SolicitudFirmaDocumentoFactory> */
    use HasFactory;

    public const DISCO = 'local';

    protected $table = 'solicitud_firma_documentos';

    protected $fillable = [
        'solicitud_firma_id',
        'nombre',
        'origen_type',
        'origen_id',
        'archivo_original',
        'archivo_actual',
        'hash_actual',
        'version',
        'paginas',
        'estado',
        'completado_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoDocumentoFirma::class,
            'version' => 'integer',
            'paginas' => 'array',
            'completado_at' => 'datetime',
        ];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirma::class, 'solicitud_firma_id');
    }

    public function origen(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * El PDF de origen, si su estado de firma depende de este documento.
     */
    public function origenVinculado(): ?Model
    {
        $origen = $this->origen;

        return $origen !== null
            && in_array(TieneEstadoFirma::class, class_uses_recursive($origen), true)
            && $origen->getAttribute('firma_documento_id') === $this->getKey()
                ? $origen
                : null;
    }

    public function firmas(): HasMany
    {
        return $this->hasMany(SolicitudFirmaFirma::class, 'documento_id');
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(SolicitudFirmaVersion::class, 'documento_id')->orderBy('version');
    }

    /**
     * Completado cuando ya no quedan firmas pendientes.
     */
    public function completarSiCorresponde(): void
    {
        if ($this->estado === EstadoDocumentoFirma::EnCurso
            && ! $this->firmas()->where('estado', EstadoFirma::Pendiente)->exists()) {
            $this->update(['estado' => EstadoDocumentoFirma::Completado, 'completado_at' => now()]);
        }
    }

    public function totalPaginas(): int
    {
        return count($this->paginas ?? []);
    }
}
