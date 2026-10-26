<?php

namespace Database\Factories;

use App\Enums\EstadoCertificadoFirma;
use App\Models\CertificadoFirma;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Por defecto crea una firma recién subida (pendiente de activación), sin
 * archivo real ni datos del certificado. Los estados `activo()` y `vencido()`
 * rellenan los datos que se obtienen al activar.
 *
 * @extends Factory<CertificadoFirma>
 */
class CertificadoFirmaFactory extends Factory
{
    protected $model = CertificadoFirma::class;

    public function definition(): array
    {
        return [
            'usuario_id' => User::factory(),
            'archivo' => 'firma-digital/certificados/'.fake()->uuid().'.p12.enc',
            'nombre_en_sello' => fake()->name(),
            'cargo_en_sello' => fake()->jobTitle(),
            'estado' => EstadoCertificadoFirma::PendienteActivacion,
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoCertificadoFirma::Activo,
            'contrasena_p12_envuelta' => 'envuelta-de-prueba',
            'huella_sha256' => hash('sha256', fake()->uuid()),
            'titular_cn' => mb_strtoupper(fake()->name()),
            'titular_documento' => (string) fake()->numerify('#######'),
            'titular_tipo_documento' => 'CI',
            'titular_cargo' => mb_strtoupper(fake()->jobTitle()),
            'emisor' => 'Entidad Certificadora Autorizada Digicert',
            'numero_serie' => fake()->hexColor(),
            'vigente_desde' => now()->subMonth(),
            'vigente_hasta' => now()->addYear(),
            'activado_at' => now(),
        ]);
    }

    public function vencido(): static
    {
        return $this->activo()->state(fn (): array => [
            'vigente_desde' => now()->subYears(2),
            'vigente_hasta' => now()->subDay(),
        ]);
    }

    public function requiereReactivacion(): static
    {
        return $this->activo()->state(fn (): array => [
            'estado' => EstadoCertificadoFirma::RequiereReactivacion,
            'contrasena_p12_envuelta' => null,
            'contrasena_usuario_huella' => null,
        ]);
    }

    public function pendienteActivacion(): static
    {
        return $this->state(fn (): array => [
            'estado' => EstadoCertificadoFirma::PendienteActivacion,
        ]);
    }
}
