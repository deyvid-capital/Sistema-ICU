<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use Database\Factories\SoftwareCargoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Software autorizado para un cargo: las personas con ese cargo lo pueden
 * recibir con la acción «Asignar por cargo».
 */
class SoftwareCargo extends Model
{
    /** @use HasFactory<SoftwareCargoFactory> */
    use HasFactory;

    use LogsIcuActivity;

    protected $table = 'software_cargos';

    protected $fillable = [
        'software_id',
        'cargo_id',
    ];

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'software_id', 'id_software');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cargo_id', 'id_cargo');
    }
}
