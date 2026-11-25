<?php

namespace App\Models;

use App\Enums\CategoriasSoftware;
use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\PreventsForceDeleteWithDependents;
use Database\Factories\CategoriaSoftwareFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo de categorías de software.
 *
 * El «codigo» enlaza la categoría con CategoriasSoftware: el formulario de
 * alta/baja y la matriz de perfiles ubican cada software en su columna por ese
 * código. Una categoría nueva sin caso en el enum se lista igual en la matriz.
 */
class CategoriaSoftware extends Model
{
    /** @use HasFactory<CategoriaSoftwareFactory> */
    use HasFactory;

    use LogsIcuActivity;
    use PreventsForceDeleteWithDependents;

    protected static array $forceDeleteGuardedRelations = ['software'];

    protected $table = 'categorias_software';

    protected $primaryKey = 'id_categoria';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
    ];

    /**
     * Software que pertenece a la categoría.
     */
    public function software(): HasMany
    {
        return $this->hasMany(Software::class, 'categoria_id', 'id_categoria');
    }

    /**
     * Categoría del catálogo para un caso del enum; la crea si aún no existe.
     */
    public static function deEnum(CategoriasSoftware $categoria): self
    {
        return self::query()->firstOrCreate(
            ['codigo' => $categoria->value],
            ['nombre' => $categoria->getLabel()],
        );
    }

    /**
     * Caso del enum que corresponde al código, si existe.
     */
    public function enum(): ?CategoriasSoftware
    {
        return CategoriasSoftware::tryFrom((string) $this->codigo);
    }

    /**
     * Color de la insignia: el del enum o gris para categorías nuevas.
     *
     * @return string|array<int|string, string>
     */
    public function color(): string|array
    {
        return $this->enum()?->getColor() ?? 'gray';
    }
}
