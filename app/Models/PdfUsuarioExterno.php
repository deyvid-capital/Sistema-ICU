<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\TieneEstadoFirma;
use App\Services\Archivos\ArchivosService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF oficial del Formulario de Usuario Externo. Se puede enviar a firmar
 * digitalmente (TieneEstadoFirma) como los demás PDF de origen.
 */
class PdfUsuarioExterno extends Model
{
    use LogsIcuActivity;
    use TieneEstadoFirma;

    public static function documentDisk(): string
    {
        return app(ArchivosService::class)->disco();
    }

    public static function documentDirectory(): string
    {
        return app(ArchivosService::class)->directorioPdf('pdf_usuarios_externos');
    }

    public $timestamps = false;

    protected $table = 'pdf_usuarios_externos';

    protected $fillable = [
        'nombre',
        'url',
        'usuario_externo_id',
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
        static::deleting(function (PdfUsuarioExterno $pdf): void {
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

        return route('archivos.pdf.ver', ['tipo' => 'pdf_usuario_externo', 'id' => $this->getKey()]);
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

    public function usuarioExterno(): BelongsTo
    {
        return $this->belongsTo(UsuarioExterno::class, 'usuario_externo_id')->withTrashed();
    }
}
