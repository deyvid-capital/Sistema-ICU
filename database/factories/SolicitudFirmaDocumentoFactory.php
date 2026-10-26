<?php

namespace Database\Factories;

use App\Enums\EstadoDocumentoFirma;
use App\Models\SolicitudFirma;
use App\Models\SolicitudFirmaDocumento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudFirmaDocumento>
 */
class SolicitudFirmaDocumentoFactory extends Factory
{
    protected $model = SolicitudFirmaDocumento::class;

    public function definition(): array
    {
        $archivo = 'firma-digital/solicitudes/'.fake()->uuid().'/original.pdf';

        return [
            'solicitud_firma_id' => SolicitudFirma::factory(),
            'nombre' => fake()->bothify('FUE-####').'.pdf',
            'archivo_original' => $archivo,
            'archivo_actual' => $archivo,
            'hash_actual' => hash('sha256', fake()->uuid()),
            'version' => 0,
            'paginas' => [['numero' => 1, 'ancho' => 612.0, 'alto' => 792.0, 'rotacion' => 0]],
            'estado' => EstadoDocumentoFirma::Borrador,
        ];
    }
}
