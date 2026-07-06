<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccesoVirtual extends Model
{

    protected $table = 'accesos_virtual';

    public $incrementing = false;

    protected $fillable = [
        'internet_id',
        'virtual_id',
        'vpn_id',
    ];

    public function accesoInternet(): BelongsTo
    {
        return $this->belongsTo(AccesoInternet::class, 'internet_id');
    }

    public function virtual(): BelongsTo
    {
        return $this->belongsTo(Virtual::class, 'virtual_id');
    }

    public function recursoVpn(): BelongsTo
    {
        return $this->belongsTo(RecursoVpn::class, 'vpn_id');
    }
}
