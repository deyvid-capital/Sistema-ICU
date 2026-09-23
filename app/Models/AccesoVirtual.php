<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUsuario;
use App\Models\Concerns\GuardsAgainstTrashedRelations;
use App\Models\Concerns\LogsIcuActivity;
use App\Models\Concerns\TogglesEstadoOnSoftDelete;
use App\Models\Concerns\WarnsOnInactiveCatalogRelations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccesoVirtual extends Model
{
    use BelongsToUsuario;
    use GuardsAgainstTrashedRelations;
    use HasFactory;
    use LogsIcuActivity;
    use SoftDeletes;
    use TogglesEstadoOnSoftDelete;
    use WarnsOnInactiveCatalogRelations;

    protected static array $trashGuardedRelations = ['usuario'];

    /**
     * @var array<string, array{label: string, attribute: string}>
     */
    protected static array $inactiveWarningRelations = [
        'virtual' => ['label' => 'la máquina virtual', 'attribute' => 'maquina_virtual'],
        'recursoVpn' => ['label' => 'el recurso VPN', 'attribute' => 'nombre_vpn'],
    ];

    protected $table = 'accesos_virtuales';

    protected $fillable = [
        'usuario_id',
        'virtual_id',
        'vpn_id',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
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
