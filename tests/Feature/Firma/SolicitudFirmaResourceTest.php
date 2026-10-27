<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\Pages\CreateSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\Pages\ListSolicitudesFirma;
use App\Filament\Resources\SolicitudesFirma\Pages\ViewSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\PdfEquipo;
use App\Models\SolicitudFirma;
use App\Models\User;
use App\Services\Firma\CrearSolicitudFirma;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\TestCase;

class SolicitudFirmaResourceTest extends TestCase
{
    use FakeFirmador;
    use RefreshDatabase;

    private User $preparador;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
        Notification::fake();
        $this->fakeFirmador();

        // skipAuthorization() de otros tests es global (ver trampas de tests).
        SolicitudFirmaResource::skipAuthorization(false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rol = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
        $rol->givePermissionTo(Permission::firstOrCreate(['name' => 'Create:SolicitudFirma', 'guard_name' => 'web']));

        $this->preparador = User::factory()->create();
        $this->preparador->assignRole($rol);
        $this->actingAs($this->preparador);
    }

    private function firmante(string $nombre): User
    {
        $usuario = $this->titular(['name' => $nombre]);
        $usuario->assignRole(Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']));
        $this->certificadoActivo($usuario);

        return $usuario;
    }

    private function solicitudDe(User $creador, User $firmante): SolicitudFirma
    {
        return app(CrearSolicitudFirma::class)(
            $creador,
            ['asunto' => 'Acta de entrega'],
            [['nombre' => 'acta.pdf', 'contenido' => $this->pdfDePrueba()]],
            [(string) $firmante->id],
        );
    }

    public function test_preparar_un_envio_con_documentos_del_sistema_y_subidos(): void
    {
        $juanito = $this->firmante('Juanito');
        $carlos = $this->firmante('Carlos');
        Storage::disk('public')->put('pdf-equipos/entrega/FUE-0042.pdf', $this->pdfDePrueba());
        $pdf = PdfEquipo::factory()->create(['url' => 'pdf-equipos/entrega/FUE-0042.pdf']);

        Livewire::test(CreateSolicitudFirma::class)
            ->fillForm([
                'documentos_sistema' => ["pdf_equipo:{$pdf->id}"],
                'documentos_subidos' => [UploadedFile::fake()->createWithContent('anexo.pdf', $this->pdfDePrueba(paginas: 3))],
                'orden_obligatorio' => false,
                'firmantes' => [(string) $juanito->id, (string) $carlos->id],
                'asunto' => 'Entrega de laptop',
                'mensaje' => 'Por favor revisar y firmar',
                'recordatorio_cada_horas' => 24,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Envío guardado como borrador')
            ->assertRedirect();

        $solicitud = SolicitudFirma::query()->sole();
        $this->assertSame('Entrega de laptop', $solicitud->asunto);
        $this->assertSame(['FUE-0042.pdf', 'anexo.pdf'], $solicitud->documentos()->orderBy('id')->pluck('nombre')->all());
        $this->assertSame(3, $solicitud->documentos()->where('nombre', 'anexo.pdf')->sole()->totalPaginas());
        $this->assertSame([$juanito->id, $carlos->id], $solicitud->firmantes->pluck('usuario_id')->all());
        $this->assertSame([], Storage::disk('local')->allFiles('livewire-tmp'));
    }

    public function test_con_orden_obligatorio_respeta_el_orden_de_la_lista(): void
    {
        $juanito = $this->firmante('Juanito');
        $pedro = $this->firmante('Pedro');

        Livewire::test(CreateSolicitudFirma::class)
            ->fillForm([
                'documentos_subidos' => [UploadedFile::fake()->createWithContent('acta.pdf', $this->pdfDePrueba())],
                'orden_obligatorio' => true,
                'asunto' => 'Recepción',
            ])
            // Reemplaza la fila vacía por defecto. Estado interno del repeater simple:
            // un ítem por fila con el valor del Select.
            ->set('data.firmantes_en_orden', [
                'a' => ['usuario_id' => (string) $pedro->id],
                'b' => ['usuario_id' => (string) $juanito->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $solicitud = SolicitudFirma::query()->sole();
        $this->assertTrue($solicitud->orden_obligatorio);
        $this->assertSame([$pedro->id, $juanito->id], $solicitud->firmantes->pluck('usuario_id')->all());
    }

    public function test_por_defecto_el_envio_no_tiene_fecha_limite(): void
    {
        Livewire::test(CreateSolicitudFirma::class)
            ->assertSchemaStateSet(['con_fecha_limite' => false])
            ->fillForm([
                'documentos_subidos' => [UploadedFile::fake()->createWithContent('acta.pdf', $this->pdfDePrueba())],
                'firmantes' => [(string) $this->firmante('Juanito')->id],
                'asunto' => 'Sin plazo',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertNull(SolicitudFirma::query()->sole()->fecha_limite);
    }

    public function test_al_activar_la_fecha_limite_es_obligatoria_y_se_guarda(): void
    {
        $firmante = (string) $this->firmante('Juanito')->id;
        $datos = [
            'documentos_subidos' => [UploadedFile::fake()->createWithContent('acta.pdf', $this->pdfDePrueba())],
            'firmantes' => [$firmante],
            'asunto' => 'Con plazo',
            'con_fecha_limite' => true,
        ];

        Livewire::test(CreateSolicitudFirma::class)
            ->fillForm([...$datos, 'fecha_limite' => null])
            ->call('create')
            ->assertHasFormErrors(['fecha_limite' => 'required']);

        Livewire::test(CreateSolicitudFirma::class)
            ->fillForm([...$datos, 'fecha_limite' => today()->addDays(10)->toDateString()])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(SolicitudFirma::query()->sole()->fecha_limite->isSameDay(today()->addDays(10)));
    }

    public function test_exige_al_menos_un_documento(): void
    {
        Livewire::test(CreateSolicitudFirma::class)
            ->fillForm([
                'firmantes' => [(string) $this->firmante('Juanito')->id],
                'asunto' => 'Sin documentos',
            ])
            ->call('create')
            ->assertHasFormErrors(['documentos_sistema' => 'required_without']);
    }

    public function test_un_usuario_sin_firma_no_se_puede_elegir(): void
    {
        $sinFirma = $this->titular(['name' => 'Sin Firma']);

        Livewire::test(CreateSolicitudFirma::class)
            ->fillForm([
                'documentos_subidos' => [UploadedFile::fake()->createWithContent('acta.pdf', $this->pdfDePrueba())],
                'firmantes' => [(string) $sinFirma->id],
                'asunto' => 'X',
            ])
            ->call('create')
            ->assertHasFormErrors(['firmantes.0']);

        $this->assertDatabaseCount('solicitudes_firma', 0);
    }

    public function test_un_pdf_con_contrasena_muestra_el_error_y_no_crea_nada(): void
    {
        Livewire::test(CreateSolicitudFirma::class)
            ->fillForm([
                'documentos_subidos' => [UploadedFile::fake()->createWithContent('protegido.pdf', $this->pdfDePrueba('CIFRADO'))],
                'firmantes' => [(string) $this->firmante('Juanito')->id],
                'asunto' => 'X',
            ])
            ->call('create')
            ->assertNotified('No se pudo preparar el envío');

        $this->assertDatabaseCount('solicitudes_firma', 0);
    }

    public function test_sin_permiso_no_se_puede_preparar_un_envio(): void
    {
        $this->actingAs($this->firmante('Juanito'));

        $this->get(SolicitudFirmaResource::getUrl('create', panel: 'admin'))->assertForbidden();
    }

    public function test_pestanas_de_la_lista(): void
    {
        $juanito = $this->firmante('Juanito');
        $this->certificadoActivo($this->preparador);
        $enviado = $this->solicitudDe($this->preparador, $juanito);
        $paraFirmar = $this->solicitudDe($this->firmante('Otro'), $this->preparador->fresh());

        Livewire::test(ListSolicitudesFirma::class)
            ->set('activeTab', 'enviados')
            ->assertCanSeeTableRecords([$enviado])
            ->assertCanNotSeeTableRecords([$paraFirmar]);

        $this->actingAs($juanito);

        Livewire::test(ListSolicitudesFirma::class)
            ->set('activeTab', 'pendientes')
            ->assertCanSeeTableRecords([$enviado])
            ->assertCanNotSeeTableRecords([$paraFirmar]);
    }

    public function test_solo_el_creador_y_los_firmantes_ven_el_envio_y_sus_documentos(): void
    {
        $juanito = $this->firmante('Juanito');
        $solicitud = $this->solicitudDe($this->preparador, $juanito);
        $documento = $solicitud->documentos()->sole();

        $this->get(SolicitudFirmaResource::getUrl('view', ['record' => $solicitud], panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('acta.pdf')
            ->assertSee('Juanito');

        $this->get(route('firma.documentos.ver', $documento))
            ->assertSuccessful()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($juanito);
        $this->get(route('firma.documentos.descargar', $documento))->assertSuccessful();

        $this->actingAs($this->firmante('Ajeno'));
        $this->get(SolicitudFirmaResource::getUrl('view', ['record' => $solicitud], panel: 'admin'))->assertNotFound();
        $this->get(route('firma.documentos.ver', $documento))->assertForbidden();
    }

    public function test_el_creador_elimina_su_borrador(): void
    {
        $solicitud = $this->solicitudDe($this->preparador, $this->firmante('Juanito'));

        Livewire::test(ViewSolicitudFirma::class, ['record' => $solicitud->getRouteKey()])
            ->callAction(DeleteAction::class)
            ->assertNotified('Borrador eliminado');

        $this->assertDatabaseCount('solicitudes_firma', 0);
    }

    public function test_un_envio_en_curso_no_se_puede_eliminar(): void
    {
        $solicitud = $this->solicitudDe($this->preparador, $this->firmante('Juanito'));
        $solicitud->update(['estado' => EstadoSolicitudFirma::EnCurso]);

        Livewire::test(ViewSolicitudFirma::class, ['record' => $solicitud->getRouteKey()])
            ->assertActionHidden(DeleteAction::class);
    }
}
