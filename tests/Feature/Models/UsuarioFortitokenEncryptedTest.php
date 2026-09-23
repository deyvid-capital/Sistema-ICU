<?php

namespace Tests\Feature\Models;

use App\Models\User;
use App\Models\UsuarioFortitoken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UsuarioFortitokenEncryptedTest extends TestCase
{
    use RefreshDatabase;

    public function test_serial_is_encrypted_in_the_database_and_readable_on_the_model(): void
    {
        $user = User::factory()->create();
        $token = UsuarioFortitoken::factory()->create([
            'usuario_id' => $user->id,
            'serial' => 'FTK-12345678',
            'estado' => true,
        ]);

        $raw = DB::table('usuario_fortitokens')->where('id', $token->id)->value('serial');

        $this->assertNotSame('FTK-12345678', $raw);
        $this->assertSame('FTK-12345678', $token->fresh()->serial);
    }

    public function test_serial_is_excluded_from_activity_log_properties(): void
    {
        $user = User::factory()->create();

        $token = UsuarioFortitoken::factory()->create([
            'usuario_id' => $user->id,
            'serial' => 'FTK-SECRET-99',
            'estado' => true,
        ]);

        $token->update(['estado' => false, 'serial' => 'FTK-SECRET-00']);

        $rows = DB::table('activity_log')
            ->where('subject_type', UsuarioFortitoken::class)
            ->where('subject_id', $token->id)
            ->pluck('properties');

        $this->assertNotEmpty($rows);

        foreach ($rows as $json) {
            $this->assertStringNotContainsString('FTK-SECRET', (string) $json);
        }
    }
}
