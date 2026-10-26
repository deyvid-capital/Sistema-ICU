<?php

namespace App\Models;

use App\Enums\EstadoCertificadoFirma;
use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\LogsIcuActivity;
use Database\Factories\CertificadoFirmaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Firma digital (.p12) de un usuario.
 *
 * `archivo` apunta al .p12 cifrado (BovedaCertificados) y `contrasena_p12_envuelta`
 * guarda la contraseña del .p12 cifrada con una llave derivada de la contraseña del
 * sistema del titular (EnvolturaContrasena). Ninguno de los dos se expone ni se audita.
 */
class CertificadoFirma extends Model
{
    use BelongsToUsuario;

    /** @use HasFactory<CertificadoFirmaFactory> */
    use HasFactory;

    use LogsIcuActivity;
    use SoftDeletes;

    protected $table = 'certificados_firma';

    protected $fillable = [
        'usuario_id',
        'archivo',
        'contrasena_p12_envuelta',
        'contrasena_usuario_huella',
        'huella_sha256',
        'titular_cn',
        'titular_documento',
        'titular_tipo_documento',
        'titular_cargo',
        'titular_organizacion',
        'emisor',
        'numero_serie',
        'vigente_desde',
        'vigente_hasta',
        'nombre_en_sello',
        'cargo_en_sello',
        'estado',
        'activado_at',
        'reemplaza_a_id',
        'intentos_fallidos',
        'bloqueado_hasta',
        'cargado_por',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'archivo',
        'contrasena_p12_envuelta',
        'contrasena_usuario_huella',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => EstadoCertificadoFirma::class,
            'vigente_desde' => 'datetime',
            'vigente_hasta' => 'datetime',
            'activado_at' => 'datetime',
            'bloqueado_hasta' => 'datetime',
            'intentos_fallidos' => 'integer',
        ];
    }

    public function cargadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cargado_por')->withTrashed();
    }

    public function reemplazaA(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reemplaza_a_id')->withTrashed();
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVigentes(Builder $query): void
    {
        $query->whereIn('estado', EstadoCertificadoFirma::vigentes());
    }

    public function estaActivo(): bool
    {
        return $this->estado === EstadoCertificadoFirma::Activo;
    }

    public function estaVigente(): bool
    {
        return $this->vigente_desde !== null
            && $this->vigente_hasta !== null
            && $this->vigente_desde->isPast()
            && $this->vigente_hasta->isFuture();
    }

    public function puedeFirmar(): bool
    {
        return $this->estaActivo()
            && $this->estaVigente()
            && filled($this->contrasena_p12_envuelta)
            && ! $this->estaBloqueado();
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture();
    }

    public function diasParaVencer(): ?int
    {
        return $this->vigente_hasta === null
            ? null
            : (int) now()->startOfDay()->diffInDays($this->vigente_hasta->copy()->startOfDay(), absolute: false);
    }

    /**
     * Texto del sello visible. La fecha es la del momento de firmar, en hora de
     * Bolivia (reloj del servidor; el sello de tiempo TSA queda para después).
     *
     * @return array{nombre: string, cargo: ?string, fecha: string}
     */
    public function datosSello(): array
    {
        return [
            'nombre' => $this->nombre_en_sello,
            'cargo' => $this->cargo_en_sello,
            'fecha' => now()->timezone((string) config('firma.sello.zona_horaria'))->format('d/m/Y H:i')
                .' '.config('firma.sello.abreviatura_zona'),
        ];
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcept(): array
    {
        return [
            'archivo',
            'contrasena_p12_envuelta',
            'contrasena_usuario_huella',
            'password',
            'remember_token',
        ];
    }
}
