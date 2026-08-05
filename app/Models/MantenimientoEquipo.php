<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class MantenimientoEquipo extends Model
{
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use SoftDeletes;

    public const IMAGE_DISK = 'public';

    public const IMAGE_DIRECTORY = 'mantenimiento-equipos';

    public const IMAGE_MAX_SIZE_KB = 5120;

    /**
     * @var list<string>
     */
    public const IMAGE_MIME_TYPES = ['image/png', 'image/jpeg'];

    /**
     * Atributos que almacenan la ruta de una imagen en el disco público.
     *
     * @var list<string>
     */
    public const IMAGE_ATTRIBUTES = ['cap_antes', 'cap_despues'];

    protected static array $trashGuardedRelations = ['equipo'];

    protected $table = 'mantenimiento_equipo';

    protected $fillable = [
        'service_tag',
        'fecha_soporte',
        'cap_antes',
        'cap_despues',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_soporte' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (MantenimientoEquipo $mantenimiento): void {
            $mantenimiento->deleteReplacedStoredImages();
        });

        static::forceDeleting(function (MantenimientoEquipo $mantenimiento): void {
            $mantenimiento->deleteStoredImages();
        });
    }

    public function hasStoredImage(string $attribute): bool
    {
        return filled($this->{$attribute})
            && Storage::disk(self::IMAGE_DISK)->exists($this->{$attribute});
    }

    public function storedImageUrl(string $attribute): ?string
    {
        if (! $this->hasStoredImage($attribute)) {
            return null;
        }

        return Storage::disk(self::IMAGE_DISK)->url($this->{$attribute});
    }

    public function storedImageFilename(string $attribute): ?string
    {
        if (! filled($this->{$attribute})) {
            return null;
        }

        return basename((string) $this->{$attribute});
    }

    /**
     * Elimina del disco público las imágenes de captura del registro.
     *
     * @return list<string> Rutas efectivamente eliminadas.
     */
    public function deleteStoredImages(): array
    {
        $deleted = [];

        foreach (self::IMAGE_ATTRIBUTES as $attribute) {
            if ($this->deleteStoredImage($this->{$attribute})) {
                $deleted[] = (string) $this->{$attribute};
            }
        }

        return $deleted;
    }

    /**
     * Elimina las imágenes que fueron reemplazadas o vaciadas en la última actualización.
     */
    protected function deleteReplacedStoredImages(): void
    {
        foreach (self::IMAGE_ATTRIBUTES as $attribute) {
            if (! $this->wasChanged($attribute)) {
                continue;
            }

            $previousPath = $this->getOriginal($attribute);

            if ($previousPath === $this->{$attribute}) {
                continue;
            }

            $this->deleteStoredImage($previousPath);
        }
    }

    protected function deleteStoredImage(mixed $path): bool
    {
        if (blank($path)) {
            return false;
        }

        $disk = Storage::disk(self::IMAGE_DISK);

        if (! $disk->exists($path)) {
            return false;
        }

        return $disk->delete($path);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'service_tag', 'service_tag')->withTrashed();
    }
}
