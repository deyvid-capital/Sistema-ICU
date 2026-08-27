<?php

namespace Database\Factories;

use App\Models\PasswordResetChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<PasswordResetChallenge>
 */
class PasswordResetChallengeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'code_hash' => Hash::make('AB3KM7PQ'),
            'expires_at' => now()->addMinutes(10),
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'enviado_at' => now(),
            'ip' => '127.0.0.1',
        ];
    }

    public function bloqueado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'bloqueado_hasta' => now()->addMinutes(15),
            'intentos_fallidos' => (int) config('acceso.recuperacion.max_intentos_codigo', 5),
            'code_hash' => '',
        ]);
    }

    public function expirado(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
