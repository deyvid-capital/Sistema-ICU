<?php

namespace App\Models;

use App\Enums\CelularFormularioPdfTipo;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\HasDocumentoOficialCelularPdf;
use App\Models\Concerns\LogsIcuActivity;
use Database\Factories\RecepcionCelularFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecepcionCelular extends Model
{
    /** @use HasFactory<RecepcionCelularFactory> */
    use GuardsAgainstTrashedRelations;

    use HasDocumentoOficialCelularPdf;
    use HasFactory;
    use LogsIcuActivity;
    use SoftDeletes;

    protected static array $trashGuardedRelations = ['asignacion'];

    protected $table = 'recepciones_celulares';

    protected $fillable = [
        'asignacion_cel_id',
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

    /**
     * Get the phone assignment that owns the reception.
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsigCelular::class, 'asignacion_cel_id')->withTrashed();
    }

    /**
     * Get all of the reception's PDF files.
     */
    public function pdfs(): MorphMany
    {
        return $this->morphMany(PdfCelular::class, 'documento');
    }

    /**
     * Get all of the reception's signers.
     */
    public function firmantes(): MorphMany
    {
        return $this->morphMany(Firmante::class, 'documento');
    }

    public static function formularioPdfTipo(): CelularFormularioPdfTipo
    {
        return CelularFormularioPdfTipo::Recepcion;
    }
}
