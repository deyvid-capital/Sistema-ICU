<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\MantenimientoEquipos\Pages\CreateMantenimientoEquipo;
use App\Filament\Resources\MantenimientoEquipos\Schemas\MantenimientoEquipoForm;
use App\Models\Equipo;
use App\Models\MantenimientoEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MantenimientoEquipoCapturaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.env' => 'local']);
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_build_stored_image_filename_uses_moment_service_tag_and_date(): void
    {
        $this->assertSame(
            'antes_ST100001_2026-07-20.png',
            MantenimientoEquipoForm::buildStoredImageFilename('antes', 'ST100001', '2026-07-20', 'png'),
        );

        $this->assertSame(
            'despues_ST100001_2026-07-20.jpg',
            MantenimientoEquipoForm::buildStoredImageFilename('despues', 'ST100001', '2026-07-20', 'JPEG'),
        );
    }

    public function test_build_stored_image_filename_falls_back_when_service_tag_is_missing(): void
    {
        $this->assertSame(
            'antes_sin-equipo_'.now()->format('Y-m-d').'.png',
            MantenimientoEquipoForm::buildStoredImageFilename('antes', null, null, null),
        );
    }

    public function test_it_stores_captures_as_images_named_by_moment_and_service_tag(): void
    {
        Storage::fake('public');

        $equipo = Equipo::factory()->create(['service_tag' => 'ST100001']);

        Livewire::test(CreateMantenimientoEquipo::class)
            ->fillForm([
                'service_tag' => $equipo->service_tag,
                'fecha_soporte' => '2026-07-20',
            ])
            ->set('data.cap_antes', [UploadedFile::fake()->image('captura-antes.png')])
            ->set('data.cap_despues', [UploadedFile::fake()->image('captura-despues.jpg')])
            ->call('create')
            ->assertHasNoFormErrors();

        $mantenimiento = MantenimientoEquipo::query()->latest('id')->firstOrFail();

        $this->assertSame('mantenimiento-equipos/antes_ST100001_2026-07-20.png', $mantenimiento->cap_antes);
        $this->assertSame('mantenimiento-equipos/despues_ST100001_2026-07-20.jpg', $mantenimiento->cap_despues);
        Storage::disk('public')->assertExists($mantenimiento->cap_antes);
        Storage::disk('public')->assertExists($mantenimiento->cap_despues);
    }

    public function test_it_rejects_images_over_five_megabytes(): void
    {
        Storage::fake('public');

        $equipo = Equipo::factory()->create(['service_tag' => 'ST100002']);

        Livewire::test(CreateMantenimientoEquipo::class)
            ->fillForm([
                'service_tag' => $equipo->service_tag,
                'fecha_soporte' => '2026-07-20',
            ])
            ->set('data.cap_antes', [UploadedFile::fake()->image('grande.png')->size(5121)])
            ->call('create')
            ->assertHasFormErrors(['cap_antes']);

        $this->assertSame(0, MantenimientoEquipo::query()->count());
    }

    public function test_it_rejects_files_that_are_not_png_or_jpg(): void
    {
        Storage::fake('public');

        $equipo = Equipo::factory()->create(['service_tag' => 'ST100003']);

        Livewire::test(CreateMantenimientoEquipo::class)
            ->fillForm([
                'service_tag' => $equipo->service_tag,
                'fecha_soporte' => '2026-07-20',
            ])
            ->set('data.cap_despues', [UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf')])
            ->call('create')
            ->assertHasFormErrors(['cap_despues']);

        $this->assertSame(0, MantenimientoEquipo::query()->count());
    }
}
