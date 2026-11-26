<?php

namespace App\Models;

use App\Enums\EquipoFormularioPdfTipo;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\HasDocumentoOficialPdf;
use App\Models\Concerns\LogsIcuActivity;
use Database\Factories\NotaRecepcionEquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaRecepcionEquipo extends Model
{
    /** @use HasFactory<NotaRecepcionEquipoFactory> */
    use GuardsAgainstTrashedRelations;

    use HasDocumentoOficialPdf;
    use HasFactory;
    use LogsIcuActivity;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['equipo', 'personalExternoSoporte'];

    protected $table = 'notas_recepcion_equipos';

    protected $fillable = [
        'equipo_id',
        'personal_externo_soporte_id',
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

    public function personalExternoSoporte(): BelongsTo
    {
        return $this->belongsTo(PersonalExternoSoporteTecnico::class, 'personal_externo_soporte_id')->withTrashed();
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
        return EquipoFormularioPdfTipo::NotaRecepcion;
    }
}
