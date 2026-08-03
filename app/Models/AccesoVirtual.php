<?php

namespace App\Models;

use App\Models\Concerns\WarnsOnInactiveCatalogRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccesoVirtual extends Model
{
    use HasFactory;
    use SoftDeletes;
    use WarnsOnInactiveCatalogRelations;

    /**
     * @var array<string, array{label: string, attribute: string}>
     */
    protected static array $inactiveWarningRelations = [
        'virtual' => ['label' => 'la máquina virtual', 'attribute' => 'maquina_virtual'],
        'recursoVpn' => ['label' => 'el recurso VPN', 'attribute' => 'nombre_vpn'],
    ];

    protected $table = 'accesos_virtual';

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
