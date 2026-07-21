<?php

namespace App\Models;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\HasDocumentoOficialPdf;
use Database\Factories\NotaSalidaEquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaSalidaEquipo extends Model
{
    /** @use HasFactory<NotaSalidaEquipoFactory> */
    use GuardsAgainstTrashedRelations;

    use HasDocumentoOficialPdf;
    use HasFactory;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['equipo', 'usuarioExterno'];

    protected $table = 'nota_salida_equipos';

    protected $fillable = [
        'equipo_id',
        'usuario_externo_id',
        'numero',
        'fecha',
        'nota',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id', 'service_tag')->withTrashed();
    }

    public function usuarioExterno(): BelongsTo
    {
        return $this->belongsTo(UsuarioExterno::class, 'usuario_externo_id')->withTrashed();
    }

    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }

    public function pdfs(): MorphMany
    {
        return $this->morphMany(PdfEquipo::class, 'documento');
    }

    public static function formularioPdfTipo(): EquipoFormularioPdfTipo
    {
        return EquipoFormularioPdfTipo::NotaSalida;
    }
}
