<?php

namespace App\Models;

use App\Models\Concerns\GuardsAgainstTrashedRelations;
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
    use SoftDeletes;

    public const DOCUMENT_DISK = 'public';

    protected static array $trashGuardedRelations = ['equipo'];

    protected $table = 'recibo_equipo';

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
            && Storage::disk(self::DOCUMENT_DISK)->exists($this->url);
    }

    public function storedDocumentUrl(): ?string
    {
        if (! $this->hasStoredDocument()) {
            return null;
        }

        return Storage::disk(self::DOCUMENT_DISK)->url($this->url);
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
        return Storage::disk(self::DOCUMENT_DISK)->download(
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

        return Storage::disk(self::DOCUMENT_DISK)->delete($this->url);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_id', 'service_tag')->withTrashed();
    }
}
