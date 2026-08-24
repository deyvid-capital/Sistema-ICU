<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use Database\Factories\PdfEquipoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfEquipo extends Model
{
    /** @use HasFactory<PdfEquipoFactory> */
    use HasFactory;

    use LogsIcuActivity;
    use SoftDeletes;

    public const DOCUMENT_DISK = 'public';

    protected $table = 'pdf_equipos';

    protected $fillable = [
        'nombre',
        'documento_id',
        'documento_type',
        'fecha',
        'url',
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
        static::forceDeleting(function (PdfEquipo $pdf): void {
            $pdf->storedDocumentWasDeleted = $pdf->deleteStoredDocument();
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

    public function deleteStoredDocument(): bool
    {
        if (! $this->hasStoredDocument()) {
            return false;
        }

        return Storage::disk(self::DOCUMENT_DISK)->delete($this->url);
    }

    public function documento(): MorphTo
    {
        return $this->morphTo();
    }
}
