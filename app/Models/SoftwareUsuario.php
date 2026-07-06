<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareUsuario extends Model
{
    use SoftDeletes;

    protected $table = 'software_usuarios';

    protected $fillable = [
        'usuario_id',
        'id_software',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the assignment.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Get the software for the assignment.
     */
    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'id_software', 'id_software');
    }
}
