<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\HasDocumentoOficialMovimientoPdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Movimiento extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use HasDocumentoOficialMovimientoPdf;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $fillable = [
        'usuario_id',
        'tipo',
        'fecha',
        'cargo_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id', 'id_cargo');
    }

    public function pdfs(): HasMany
    {
        return $this->hasMany(PdfMovimiento::class, 'movimiento_id');
    }

    /**
     * Valida la coherencia de un movimiento contra el historial del usuario.
     * Devuelve la lista de errores que impiden guardar (vacía si es válido).
     *
     * En edición ($esEdicion = true) solo se evita el duplicado exacto, para
     * permitir corregir libremente la fecha (o el cargo) de un movimiento.
     *
     * @param  array{usuario_id?:mixed,tipo?:mixed,fecha?:mixed,cargo_id?:mixed}  $data
     * @return list<string>
     */
    public static function erroresDeValidacion(array $data, ?int $ignoreId = null, bool $esEdicion = false): array
    {
        $errores = [];
        $usuarioId = $data['usuario_id'] ?? null;
        $tipo = $data['tipo'] ?? null;
        $cargoId = isset($data['cargo_id']) ? (int) $data['cargo_id'] : null;
        $fecha = ! empty($data['fecha']) ? Carbon::parse($data['fecha'])->startOfDay() : null;

        // Sin estos datos base dejamos que las reglas 'required' del formulario actúen.
        if (! $usuarioId || ! $tipo || ! $fecha) {
            return $errores;
        }

        if (! $cargoId) {
            return ['Debes seleccionar un cargo para el movimiento.'];
        }

        $movimientos = static::query()
            ->where('usuario_id', $usuarioId)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        // B1 - No dos movimientos idénticos (misma fecha, mismo cargo y mismo tipo).
        // El tipo importa: una Alta y su Baja del mismo día comparten cargo y NO son duplicado.
        $duplicado = $movimientos->first(
            fn (self $m): bool => $m->fecha->isSameDay($fecha)
                && (int) $m->cargo_id === $cargoId
                && $m->tipo === $tipo,
        );

        if ($duplicado) {
            $errores[] = "Ya existe un movimiento de tipo {$tipo} para este usuario en esa fecha con el mismo cargo.";
        }

        // En edición somos flexibles: basta con evitar el duplicado exacto.
        if ($esEdicion) {
            return $errores;
        }

        // Estado actual del usuario = movimiento más reciente.
        $ultimo = $movimientos->first();

        if ($tipo === 'Alta' && $ultimo && $ultimo->tipo === 'Alta') {
            // B2 - No dar de Alta si ya está de Alta.
            $errores[] = "El usuario ya está de Alta (cargo #{$ultimo->cargo_id}). Debes registrar la Baja antes de una nueva Alta.";
        }

        if ($tipo === 'Baja') {
            if (! $ultimo) {
                // B5 - El primer movimiento debe ser una Alta.
                $errores[] = 'El usuario no tiene una Alta abierta. El primer movimiento debe ser una Alta.';
            } elseif ($ultimo->tipo === 'Baja') {
                // B3 - No hay Alta abierta que cerrar.
                $errores[] = 'El usuario ya está de Baja. No hay una Alta abierta para cerrar.';
            } else {
                // B4 - La Baja debe llevar el mismo cargo de la Alta que cierra.
                if ((int) $ultimo->cargo_id !== $cargoId) {
                    $errores[] = "La Baja debe cerrarse con el mismo cargo de la Alta abierta (cargo #{$ultimo->cargo_id}).";
                }

                // B7 - La Baja no puede ser anterior a la Alta que cierra.
                if ($fecha->lt($ultimo->fecha->copy()->startOfDay())) {
                    $errores[] = 'La fecha de Baja no puede ser anterior a la fecha de la Alta que cierra.';
                }
            }
        }

        return $errores;
    }

    /**
     * Avisos que no impiden guardar pero requieren confirmación del usuario.
     *
     * @param  array{usuario_id?:mixed,tipo?:mixed,fecha?:mixed,cargo_id?:mixed}  $data
     * @return list<string>
     */
    public static function avisosDeConfirmacion(array $data, ?int $ignoreId = null): array
    {
        $avisos = [];
        $usuarioId = $data['usuario_id'] ?? null;
        $tipo = $data['tipo'] ?? null;
        $fecha = ! empty($data['fecha']) ? Carbon::parse($data['fecha'])->startOfDay() : null;

        if (! $usuarioId || ! $tipo || ! $fecha) {
            return $avisos;
        }

        // A2 - Fecha futura.
        if ($fecha->isFuture()) {
            $avisos[] = 'La fecha del movimiento es futura.';
        }

        // A1 - Alta y Baja para el mismo usuario el mismo día.
        $tipoOpuesto = $tipo === 'Alta' ? 'Baja' : 'Alta';

        $existeOpuestoMismoDia = static::query()
            ->where('usuario_id', $usuarioId)
            ->where('tipo', $tipoOpuesto)
            ->whereDate('fecha', $fecha->toDateString())
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($existeOpuestoMismoDia) {
            $avisos[] = 'Se registrará un Alta y una Baja para este usuario el mismo día.';
        }

        return $avisos;
    }
}
