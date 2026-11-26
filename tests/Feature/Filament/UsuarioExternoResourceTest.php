<?php

namespace Tests\Feature\Filament;

use App\Enums\PermisoDocumentoCompartido;
use App\Filament\Clusters\Usuarios\UsuariosCluster;
use App\Filament\Resources\UsuariosExternos\Pages\CreateUsuarioExterno;
use App\Filament\Resources\UsuariosExternos\Pages\EditUsuarioExterno;
use App\Filament\Resources\UsuariosExternos\Pages\ListUsuariosExternos;
use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use App\Models\SitioSharepoint;
use App\Models\User;
use App\Models\UsuarioExterno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsuarioExternoResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $responsable;

    protected function setUp(): void
    {
        parent::setUp();

        UsuarioExternoResource::skipAuthorization();

        $this->actingAs(User::factory()->conRol()->create());

        $this->responsable = User::factory()->create();
    }

    public function test_el_recurso_esta_en_el_cluster_de_usuarios(): void
    {
        $this->assertSame(UsuariosCluster::class, UsuarioExternoResource::getCluster());
    }

    public function test_lista_los_usuarios_externos(): void
    {
        $usuariosExternos = UsuarioExterno::factory()->count(3)->create();

        Livewire::test(ListUsuariosExternos::class)
            ->assertCanSeeTableRecords($usuariosExternos);
    }

    public function test_registra_un_usuario_externo_con_sharepoint_y_onedrive(): void
    {
        $sitio = SitioSharepoint::factory()->create(['estado' => true]);

        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos([
                'sitio_sharepoint_id' => $sitio->id,
                'onedrive' => 'https://capitalsafi-my.sharepoint.com/personal/auditoria',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(UsuarioExterno::class, [
            'nombre_contacto' => 'Juan Pérez',
            'empresa' => 'Consultora XYZ',
            'cargo' => 'Consultor externo',
            'correo' => 'usuario@consultora.com',
            'responsable_id' => $this->responsable->id,
            'sitio_sharepoint_id' => $sitio->id,
            'onedrive' => 'https://capitalsafi-my.sharepoint.com/personal/auditoria',
            'motivo' => 'Auditoría',
            'estado' => true,
            'permiso' => PermisoDocumentoCompartido::Lectura->value,
        ]);
    }

    public function test_registra_un_usuario_externo_solo_con_sharepoint(): void
    {
        $sitio = SitioSharepoint::factory()->create();

        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos(['sitio_sharepoint_id' => $sitio->id]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(UsuarioExterno::class, ['sitio_sharepoint_id' => $sitio->id, 'onedrive' => null]);
    }

    public function test_registra_un_usuario_externo_solo_con_onedrive_como_nombre_simple(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos(['onedrive' => 'Carpeta Auditoría 2026']))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(UsuarioExterno::class, ['sitio_sharepoint_id' => null, 'onedrive' => 'Carpeta Auditoría 2026']);
    }

    public function test_exige_sharepoint_u_onedrive(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos())
            ->call('create')
            ->assertHasFormErrors(['sitio_sharepoint_id' => 'required_without', 'onedrive' => 'required_without']);

        $this->assertDatabaseCount(UsuarioExterno::class, 0);
    }

    public function test_valida_los_campos_obligatorios(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm([
                'nombre_contacto' => null,
                'empresa' => null,
                'correo' => null,
                'responsable_id' => null,
                'fecha_inicio' => null,
                'motivo' => null,
                'permiso' => null,
                'onedrive' => 'Carpeta',
            ])
            ->call('create')
            ->assertHasFormErrors([
                'nombre_contacto' => 'required',
                'empresa' => 'required',
                'correo' => 'required',
                'responsable_id' => 'required',
                'fecha_inicio' => 'required',
                'motivo' => 'required',
                'permiso' => 'required',
            ]);
    }

    public function test_valida_el_formato_del_correo(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos(['correo' => 'no-es-un-correo', 'onedrive' => 'Carpeta']))
            ->call('create')
            ->assertHasFormErrors(['correo' => 'email']);
    }

    public function test_la_fecha_fin_no_puede_ser_anterior_a_la_de_inicio(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos([
                'fecha_inicio' => '2026-12-15',
                'fecha_fin' => '2026-11-25',
                'onedrive' => 'Carpeta',
            ]))
            ->call('create')
            ->assertHasFormErrors(['fecha_fin' => 'after_or_equal']);
    }

    public function test_la_fecha_fin_puede_ser_el_mismo_dia_que_la_de_inicio(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos([
                'fecha_inicio' => '2026-11-26',
                'fecha_fin' => '2026-11-26',
                'onedrive' => 'Carpeta',
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(UsuarioExterno::class, ['onedrive' => 'Carpeta']);
    }

    public function test_la_fecha_fin_de_hoy_es_valida_con_la_fecha_inicio_por_defecto(): void
    {
        $datos = $this->datosValidos([
            'fecha_fin' => now()->toDateString(),
            'onedrive' => 'Carpeta',
        ]);
        unset($datos['fecha_inicio']);

        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($datos)
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_la_fecha_fin_es_opcional(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos(['fecha_fin' => null, 'onedrive' => 'Carpeta']))
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(UsuarioExterno::class, ['onedrive' => 'Carpeta', 'fecha_fin' => null]);
    }

    public function test_rechaza_un_onedrive_que_parece_url_pero_no_es_valida(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos(['onedrive' => 'https://']))
            ->call('create')
            ->assertHasFormErrors(['onedrive']);
    }

    public function test_rechaza_un_permiso_que_no_existe(): void
    {
        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos(['permiso' => 'administrador', 'onedrive' => 'Carpeta']))
            ->call('create')
            ->assertHasFormErrors(['permiso']);
    }

    public function test_no_acepta_un_responsable_en_la_papelera(): void
    {
        $this->responsable->delete();

        Livewire::test(CreateUsuarioExterno::class)
            ->fillForm($this->datosValidos(['onedrive' => 'Carpeta']))
            ->call('create')
            ->assertHasFormErrors(['responsable_id']);
    }

    public function test_editar_la_fecha_fin_reprograma_el_aviso(): void
    {
        $usuarioExterno = UsuarioExterno::factory()->create([
            'responsable_id' => $this->responsable->id,
            'onedrive' => 'Carpeta',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-11-20',
        ]);
        $usuarioExterno->forceFill(['recordatorio_enviado_at' => now()])->saveQuietly();

        Livewire::test(EditUsuarioExterno::class, ['record' => $usuarioExterno->getKey()])
            ->fillForm(['fecha_fin' => '2026-12-15'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $usuarioExterno->refresh();

        $this->assertSame('2026-12-15', $usuarioExterno->fecha_fin->toDateString());
        $this->assertNull($usuarioExterno->recordatorio_enviado_at);
        $this->assertSame('Programado para el 15/12/2026', $usuarioExterno->estadoRecordatorio());
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function datosValidos(array $cambios = []): array
    {
        return [
            'nombre_contacto' => 'Juan Pérez',
            'empresa' => 'Consultora XYZ',
            'cargo' => 'Consultor externo',
            'correo' => 'usuario@consultora.com',
            'responsable_id' => $this->responsable->id,
            'sitio_sharepoint_id' => null,
            'onedrive' => null,
            'fecha_inicio' => '2026-11-25',
            'fecha_fin' => '2026-12-15',
            'motivo' => 'Auditoría',
            'estado' => true,
            'permiso' => PermisoDocumentoCompartido::Lectura->value,
            ...$cambios,
        ];
    }
}
