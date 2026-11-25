<?php

namespace App\Models;

use App\Models\Concerns\LogsIcuActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareArea extends Model
{
    use HasFactory;
    use LogsIcuActivity;

    protected $table = 'software_areas';

    protected $fillable = [
        'software_id',
        'area_id',
    ];

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'software_id', 'id_software');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id', 'id_area');
    }
}
