<?php

namespace App\Models;

use App\Enums\FirmantePosicion;
use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\LogsIcuActivity;
use App\Support\FirmanteDocumentoConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Firmante extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use LogsIcuActivity;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $fillable = [
        'usuario_id',
        'fecha',
        'posicion',
        'documento_id',
        'documento_type',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'posicion' => FirmantePosicion::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Firmante $firmante): void {
            $firmante->assertPosicionValidaParaDocumento();
        });
    }

    /**
     * Get the parent documentable model.
     */
    public function documento(): MorphTo
    {
        return $this->morphTo();
    }

    public function assertPosicionValidaParaDocumento(): void
    {
        $documentoType = $this->documento_type;

        if (! filled($documentoType) || ! filled($this->documento_id)) {
            return;
        }

        $posicion = $this->posicion instanceof FirmantePosicion
            ? $this->posicion
            : FirmantePosicion::tryFrom((string) $this->posicion);

        if ($posicion === null) {
            throw ValidationException::withMessages([
                'posicion' => 'La posición de firma es obligatoria.',
            ]);
        }

        if (! FirmanteDocumentoConfig::allows($posicion, $documentoType)) {
            throw ValidationException::withMessages([
                'posicion' => 'La posición «'.$posicion->getLabel().'» no está permitida para este tipo de documento.',
            ]);
        }

        $duplicada = static::query()
            ->where('documento_type', $documentoType)
            ->where('documento_id', $this->documento_id)
            ->where('posicion', $posicion->value)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();

        if ($duplicada) {
            throw ValidationException::withMessages([
                'posicion' => 'Ya existe un firmante en la posición «'.$posicion->getLabel().'» para este documento.',
            ]);
        }

        if (! $this->exists) {
            $max = FirmanteDocumentoConfig::maxFirmantes($documentoType);
            $count = static::query()
                ->where('documento_type', $documentoType)
                ->where('documento_id', $this->documento_id)
                ->count();

            if ($count >= $max) {
                throw ValidationException::withMessages([
                    'posicion' => "Este documento solo admite {$max} firmante(s).",
                ]);
            }
        }
    }
}
