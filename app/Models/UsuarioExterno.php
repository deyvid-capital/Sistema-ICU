<?php

namespace App\Models;

use App\Enums\PermisoDocumentoCompartido;
use App\Models\Concerns\HasDocumentoOficialUsuarioExternoPdf;
use App\Models\Concerns\LogsIcuActivity;
use App\Services\Formularios\FormularioEquipoNumeroGenerator;
use Carbon\CarbonInterface;
use Database\Factories\UsuarioExternoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Usuario externo (por ejemplo, un auditor) con acceso a documentos compartidos
 * (SharePoint y/u OneDrive) durante un periodo.
 *
 * Recordatorio de fin: el comando usuarios-externos:recordar-fin (scheduler,
 * cada hora) avisa por correo a los administradores cuando llega la fecha fin.
 * - Programado: activo, con fecha fin y sin recordatorio_enviado_at.
 * - Si cambia la fecha fin, el observer limpia recordatorio_enviado_at y el
 *   aviso queda programado para la nueva fecha.
 * - Cancelado: el usuario externo se desactiva (estado = false) o se elimina.
 *
 * Número del formulario («001-2026»): se asigna solo al crear el registro, en
 * orden y por año, y no se edita. Ver asignarNumero().
 */
class UsuarioExterno extends Model
{
    use HasDocumentoOficialUsuarioExternoPdf;

    /** @use HasFactory<UsuarioExternoFactory> */
    use HasFactory;

    use LogsIcuActivity;
    use SoftDeletes;

    protected $table = 'usuarios_externos';

    protected $fillable = [
        'nombre_contacto',
        'empresa',
        'cargo',
        'correo',
        'responsable_id',
        'sitio_sharepoint_id',
        'onedrive',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado',
        'permiso',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'estado' => 'boolean',
            'permiso' => PermisoDocumentoCompartido::class,
            'recordatorio_enviado_at' => 'datetime',
        ];
    }

    /**
     * Clave del bloqueo de PostgreSQL que serializa la numeración.
     */
    private const BLOQUEO_NUMERACION = 7_310_001;

    protected static function booted(): void
    {
        static::creating(function (self $usuarioExterno): void {
            if (blank($usuarioExterno->numero)) {
                $usuarioExterno->numero = self::siguienteNumero();
            }
        });
    }

    /**
     * Siguiente número del año en curso («002-2026» tras «001-2026»).
     *
     * Dentro de una transacción toma un bloqueo de PostgreSQL hasta el commit,
     * para que dos altas simultáneas no reciban el mismo número. Las altas desde
     * el panel pasan por crearNumerado(), que abre esa transacción.
     */
    public static function siguienteNumero(): string
    {
        if (DB::transactionLevel() > 0 && DB::connection()->getDriverName() === 'pgsql') {
            DB::select('select pg_advisory_xact_lock(?)', [self::BLOQUEO_NUMERACION]);
        }

        return app(FormularioEquipoNumeroGenerator::class)->next(
            self::class,
            now(config('app.display_timezone'))->year,
        );
    }

    /**
     * Crea el registro con su número dentro de una transacción.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function crearNumerado(array $datos): self
    {
        return DB::transaction(fn (): self => self::query()->create($datos));
    }

    public function pdfs(): HasMany
    {
        return $this->hasMany(PdfUsuarioExterno::class, 'usuario_externo_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id')->withTrashed();
    }

    public function sitioSharepoint(): BelongsTo
    {
        return $this->belongsTo(SitioSharepoint::class, 'sitio_sharepoint_id');
    }

    /**
     * Usuarios externos cuyo recordatorio de fin toca enviar en la fecha dada.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeConRecordatorioPendiente(Builder $query, CarbonInterface $hoy): Builder
    {
        return $query
            ->where('estado', true)
            ->whereNotNull('fecha_fin')
            ->whereDate('fecha_fin', '<=', $hoy->toDateString())
            ->whereNull('recordatorio_enviado_at');
    }

    /**
     * Estado del recordatorio de fin, para mostrarlo en el panel.
     */
    public function estadoRecordatorio(): string
    {
        if ($this->fecha_fin === null) {
            return 'Sin fecha fin';
        }

        if ($this->recordatorio_enviado_at !== null) {
            return 'Enviado el '.$this->recordatorio_enviado_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i');
        }

        if ($this->trashed() || ! $this->estado) {
            return 'Cancelado';
        }

        return 'Programado para el '.$this->fecha_fin->format('d/m/Y');
    }
}
