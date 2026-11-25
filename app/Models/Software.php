<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Software extends Model
{
    use HasFactory;
    use LogsIcuActivity;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['cargos', 'usuarios', 'excepciones'];

    protected $primaryKey = 'id_software';

    protected $table = 'softwares';

    protected $fillable = [
        'categoria_id',
        'nombre_software',
        'estado',
        'fecha_autorizacion',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
            'fecha_autorizacion' => 'date',
        ];
    }

    /**
     * Categoría del catálogo a la que pertenece el software.
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaSoftware::class, 'categoria_id', 'id_categoria');
    }

    /**
     * Cargos que tienen autorizado este software.
     */
    public function cargos(): HasMany
    {
        return $this->hasMany(SoftwareCargo::class, 'software_id', 'id_software');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(SoftwareUsuario::class, 'id_software', 'id_software');
    }

    public function excepciones(): HasMany
    {
        return $this->hasMany(SoftwareExc::class, 'software_id', 'id_software');
    }
}
