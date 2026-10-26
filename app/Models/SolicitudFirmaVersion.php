<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historial de archivos de un documento: versión 0 = preparado, 1 = primera firma, etc.
 */
class SolicitudFirmaVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'solicitud_firma_versiones';

    protected $fillable = [
        'documento_id',
        'version',
        'archivo',
        'hash_sha256',
        'firma_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
        ];
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirmaDocumento::class, 'documento_id');
    }

    public function firma(): BelongsTo
    {
        return $this->belongsTo(SolicitudFirmaFirma::class, 'firma_id');
    }
}
