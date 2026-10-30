<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\PdfCelulars\Pages\ListPdfCelulars;
use App\Filament\Resources\PdfCelulars\Pages\ViewPdfCelular;
use App\Filament\Resources\PdfEquipos\Pages\ListPdfEquipos;
use App\Filament\Resources\PdfEquipos\Pages\ViewPdfEquipo;
use App\Models\PdfCelular;
use App\Models\PdfEquipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PdfResourcesViewPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Gate::before(fn () => true);

        $this->actingAs(User::factory()->create());
    }

    public function test_pdf_equipo_view_page_loads(): void
    {
        $pdf = PdfEquipo::factory()->create([
            'nombre' => 'Formulario de entrega',
            'url' => 'pdf-equipos/entrega/FUE-001-2026.pdf',
        ]);

        Storage::disk('local')->put($pdf->url, '%PDF-1.4 test');

        Livewire::test(ViewPdfEquipo::class, ['record' => $pdf->getKey()])
            ->assertOk()
            ->assertSchemaStateSet(['nombre' => 'Formulario de entrega']);
    }

    public function test_pdf_celular_view_page_loads(): void
    {
        $pdf = PdfCelular::factory()->create([
            'nombre' => 'Formulario de entrega de celular',
            'url' => 'pdf-celulares/entrega/FUC-001-2026.pdf',
        ]);

        Storage::disk('local')->put($pdf->url, '%PDF-1.4 test');

        Livewire::test(ViewPdfCelular::class, ['record' => $pdf->getKey()])
            ->assertOk()
            ->assertSchemaStateSet(['nombre' => 'Formulario de entrega de celular']);
    }

    public function test_pdf_list_pages_show_records(): void
    {
        $equipo = PdfEquipo::factory()->create();
        $celular = PdfCelular::factory()->create();

        Livewire::test(ListPdfEquipos::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$equipo]);

        Livewire::test(ListPdfCelulars::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$celular]);
    }
}
