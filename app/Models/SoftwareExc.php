<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\LogsIcuActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftwareExc extends Model
{
    use BelongsToUsuario, GuardsAgainstTrashedRelations, HasFactory, LogsIcuActivity, SoftDeletes;

    protected static array $trashGuardedRelations = ['usuario'];

    protected $table = 'software_excepciones';

    protected $fillable = [
        'usuario_id',
        'software_id',
        'tipo_excepcion',
        'motivo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'software_id', 'id_software');
    }
}
