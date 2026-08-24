<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfMovimiento extends Model
{
    use LogsIcuActivity;

    public const DOCUMENT_DISK = 'public';

    public $timestamps = false;

    protected $table = 'pdf_movimientos';

    protected $fillable = [
        'tipo',
        'nombre',
        'url',
        'movimiento_id',
        'fecha',
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
        static::deleting(function (PdfMovimiento $pdf): void {
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

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }
}
