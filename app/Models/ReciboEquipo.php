<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\LogsIcuActivity;
use App\Services\Archivos\ArchivosService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReciboEquipo extends Model
{
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use LogsIcuActivity;
    use SoftDeletes;

    public const DOCUMENT_DISK = 'public';

    public static function documentDisk(): string
    {
        return app(ArchivosService::class)->disco();
    }

    public static function documentDirectory(): string
    {
        return app(ArchivosService::class)->directorioPdf('recibo_equipos');
    }

    protected static array $trashGuardedRelations = ['equipo'];

    protected $table = 'recibos_equipos';

    protected $fillable = [
        'equipo_id',
        'fecha',
        'url',
        'observaciones',
    ];

    public bool $storedDocumentWasDeleted = false;

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (ReciboEquipo $recibo): void {
            $recibo->storedDocumentWasDeleted = $recibo->deleteStoredDocument();
        });
    }

    public function hasStoredDocument(): bool
    {
        return filled($this->url)
            && Storage::disk(self::documentDisk())->exists($this->url);
    }

    public function storedDocumentUrl(): ?string
    {
        if (! $this->hasStoredDocument()) {
            return null;
        }

        return Storage::disk(self::documentDisk())->url($this->url);
    }

    public function storedDocumentFilename(): ?string
    {
        if (! filled($this->url)) {
            return null;
        }

        return basename($this->url);
    }

    public function downloadStoredDocument(): StreamedResponse
    {
        return Storage::disk(self::documentDisk())->download(
            $this->url,
            $this->storedDocumentFilename(),
        );
    }

    /**
     * Elimina el PDF del disco público si la ruta existe.
     */
    public function deleteStoredDocument(): bool
    {
        if (! $this->hasStoredDocument()) {
            return false;
        }

        return Storage::disk(self::documentDisk())->delete($this->url);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id', 'service_tag')->withTrashed();
    }
}
