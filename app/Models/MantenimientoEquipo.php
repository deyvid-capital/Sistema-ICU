<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\HasIcuMedia;
use App\Models\Concerns\LogsIcuActivity;
use App\Services\Archivos\ArchivosService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

class MantenimientoEquipo extends Model implements HasMedia
{
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use HasIcuMedia;
    use LogsIcuActivity;
    use SoftDeletes;

    /**
     * @deprecated Usa ArchivosService::disco()
     */
    public const IMAGE_DISK = 'public';

    /**
     * @deprecated Usa colecciones Media Library
     */
    public const IMAGE_DIRECTORY = 'mantenimiento-equipos';

    public const IMAGE_MAX_SIZE_KB = 5120;

    /**
     * @var list<string>
     */
    public const IMAGE_MIME_TYPES = ['image/png', 'image/jpeg'];

    /**
     * Colecciones / atributos legacy de captura.
     *
     * @var list<string>
     */
    public const IMAGE_ATTRIBUTES = ['cap_antes', 'cap_despues'];

    protected static array $trashGuardedRelations = ['equipo'];

    protected $table = 'mantenimientos_equipos';

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

    public function registerMediaCollections(): void
    {
        $disk = $this->discoMedia();

        $this->addMediaCollection('cap_antes')
            ->useDisk($disk)
            ->singleFile()
            ->acceptsMimeTypes(self::IMAGE_MIME_TYPES);

        $this->addMediaCollection('cap_despues')
            ->useDisk($disk)
            ->singleFile()
            ->acceptsMimeTypes(self::IMAGE_MIME_TYPES);
    }

    /**
     * @return list<string>
     */
    protected function coleccionesConConversion(): array
    {
        return ['cap_antes', 'cap_despues'];
    }

    protected static function booted(): void
    {
        static::updated(function (MantenimientoEquipo $mantenimiento): void {
            $mantenimiento->deleteReplacedLegacyImages();
        });

        static::forceDeleting(function (MantenimientoEquipo $mantenimiento): void {
            $mantenimiento->deleteLegacyStoredImages();
        });
    }

    /**
     * Elimina rutas legacy en columnas (fallback previo a Media Library).
     *
     * @return list<string>
     */
    public function deleteStoredImages(): array
    {
        return $this->deleteLegacyStoredImages();
    }

    /**
     * @return list<string>
     */
    protected function deleteLegacyStoredImages(): array
    {
        $deleted = [];

        foreach (self::IMAGE_ATTRIBUTES as $attribute) {
            if ($this->hasMedia($attribute)) {
                continue;
            }

            if ($this->deleteStoredImage($this->{$attribute})) {
                $deleted[] = (string) $this->{$attribute};
            }
        }

        return $deleted;
    }

    protected function deleteReplacedLegacyImages(): void
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

        $disk = Storage::disk(app(ArchivosService::class)->disco());

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
