<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use App\Services\Archivos\ArchivosService;
use Database\Factories\PdfCelularFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfCelular extends Model
{
    /** @use HasFactory<PdfCelularFactory> */
    use HasFactory;

    use LogsIcuActivity;

    public const DOCUMENT_DISK = 'public';

    public static function documentDisk(): string
    {
        return app(ArchivosService::class)->disco();
    }

    public static function documentDirectory(): string
    {
        return app(ArchivosService::class)->directorioPdf('pdf_celulares');
    }

    protected $table = 'pdf_celulares';

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
        static::deleting(function (PdfCelular $pdf): void {
            $pdf->storedDocumentWasDeleted = $pdf->deleteStoredDocument();
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

    public function deleteStoredDocument(): bool
    {
        if (! $this->hasStoredDocument()) {
            return false;
        }

        return Storage::disk(self::documentDisk())->delete($this->url);
    }

    public function documento(): MorphTo
    {
        return $this->morphTo();
    }
}
