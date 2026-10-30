<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\TieneEstadoFirma;
use App\Services\Archivos\ArchivosService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfMovimiento extends Model
{
    use LogsIcuActivity;
    use TieneEstadoFirma;

    public static function documentDisk(): string
    {
        return app(ArchivosService::class)->disco();
    }

    public static function documentDirectory(): string
    {
        return app(ArchivosService::class)->directorioPdf('pdf_movimientos');
    }

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
            && Storage::disk(self::documentDisk())->exists($this->url);
    }

    public function storedDocumentUrl(): ?string
    {
        if (! $this->hasStoredDocument()) {
            return null;
        }

        return route('archivos.pdf.ver', ['tipo' => 'pdf_movimiento', 'id' => $this->getKey()]);
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

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class, 'movimiento_id');
    }
}
