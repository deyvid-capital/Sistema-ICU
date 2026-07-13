<?php

namespace Tests\Unit\Rules;

use App\Models\User;
use App\Rules\NotTrashed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotTrashedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_passes_for_an_active_record(): void
    {
        $user = User::factory()->create();
        $failed = false;

        (new NotTrashed(User::class))->validate('usuario_id', $user->id, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_it_fails_for_a_trashed_record(): void
    {
        $user = User::factory()->create();
        $user->delete();
        $message = null;

        (new NotTrashed(User::class))->validate('usuario_id', $user->id, function (string $fail) use (&$message): void {
            $message = $fail;
        });

        $this->assertNotNull($message);
    }

    public function test_it_passes_for_a_blank_value(): void
    {
        $failed = false;

        (new NotTrashed(User::class))->validate('usuario_id', null, function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }
}
