<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoDocumentoFirma;
use App\Enums\EstadoFirma;
use App\Enums\EstadoSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\Pages\ViewSolicitudFirma;
use App\Filament\Resources\SolicitudesFirma\SolicitudFirmaResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\Feature\Firma\Concerns\PreparaEnviosEnCurso;
use Tests\TestCase;
use ZipArchive;

class FirmarPantallaTest extends TestCase
{
    use FakeFirmador;
    use PreparaEnviosEnCurso;
    use RefreshDatabase;

    private User $creador;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
        $this->fakeFirmador();

        SolicitudFirmaResource::skipAuthorization(false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->creador = User::factory()->create();
        $this->creador->assignRole(Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']));
    }

    private function pantalla(int $solicitudId)
    {
        return Livewire::test(ViewSolicitudFirma::class, ['record' => $solicitudId]);
    }

    public function test_el_firmante_ve_el_envio_y_firma_con_su_contrasena(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $this->actingAs($juanito);

        $this->get(SolicitudFirmaResource::getUrl('view', ['record' => $solicitud], panel: 'admin'))
            ->assertSuccessful()
            ->assertSee('acta-1.pdf')
            ->assertSee(route('firma.documentos.ver', $solicitud->documentos()->sole()), escape: false);

        $this->pantalla($solicitud->id)
            ->assertActionVisible('firmar')
            ->assertActionEnabled('firmar')
            ->callAction('firmar', ['contrasena' => self::CONTRASENA_SISTEMA, 'declaro' => true])
            ->assertHasNoFormErrors()
            ->assertNotified('Documento firmado');

        $this->assertSame(EstadoFirma::Firmado, $this->firmaDe($solicitud, $juanito)->estado);
    }

    public function test_hay_que_declarar_haber_revisado_el_documento(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $this->actingAs($juanito);

        $this->pantalla($solicitud->id)
            ->callAction('firmar', ['contrasena' => self::CONTRASENA_SISTEMA, 'declaro' => false])
            ->assertHasFormErrors(['declaro' => 'accepted']);

        $this->assertSame(EstadoFirma::Pendiente, $this->firmaDe($solicitud, $juanito)->estado);
    }

    public function test_contrasena_incorrecta_muestra_los_intentos_que_quedan(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $this->actingAs($juanito);

        $this->pantalla($solicitud->id)
            ->callAction('firmar', ['contrasena' => 'equivocada', 'declaro' => true])
            ->assertNotified('No se pudo firmar');

        $this->assertSame(1, $juanito->certificadoFirmaActivo()->first()->intentos_fallidos);
    }

    public function test_firmar_varios_documentos_a_la_vez(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito], documentos: 3);
        $documentos = $solicitud->documentos()->orderBy('id')->pluck('id')->all();
        $this->actingAs($juanito);

        $this->pantalla($solicitud->id)
            ->assertActionHasLabel('firmar', 'Firmar (3)')
            ->callAction('firmar', [
                'documentos' => [$documentos[0], $documentos[2]],
                'contrasena' => self::CONTRASENA_SISTEMA,
                'declaro' => true,
            ])
            ->assertNotified('Documentos firmados');

        $this->assertSame(EstadoFirma::Firmado, $this->firmaDe($solicitud, $juanito, 0)->estado);
        $this->assertSame(EstadoFirma::Pendiente, $this->firmaDe($solicitud, $juanito, 1)->estado);
        $this->assertSame(EstadoFirma::Firmado, $this->firmaDe($solicitud, $juanito, 2)->estado);
    }

    public function test_con_orden_el_boton_espera_al_firmante_anterior(): void
    {
        $pedro = $this->firmanteConFirma('Pedro');
        $carlos = $this->firmanteConFirma('Carlos');
        $solicitud = $this->envioEnCurso($this->creador, [$pedro, $carlos], ordenObligatorio: true);
        $this->actingAs($carlos);

        $this->pantalla($solicitud->id)
            ->assertActionVisible('firmar')
            ->assertActionDisabled('firmar');

        $this->get(SolicitudFirmaResource::getUrl('view', ['record' => $solicitud], panel: 'admin'))
            ->assertSee('Esperando la firma de Pedro');
    }

    public function test_rechazar_un_documento_con_motivo(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $this->actingAs($juanito);

        $this->pantalla($solicitud->id)
            ->callAction('rechazar', ['motivo' => 'Falta el número de serie'])
            ->assertHasNoFormErrors()
            ->assertNotified('Documento rechazado');

        $this->assertSame(EstadoDocumentoFirma::Rechazado, $solicitud->documentos()->sole()->estado);
        $this->assertSame(EstadoSolicitudFirma::Finalizada, $solicitud->refresh()->estado);
    }

    public function test_quien_no_es_firmante_no_ve_firmar_ni_rechazar(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);
        $this->actingAs($this->creador);

        $this->pantalla($solicitud->id)
            ->assertActionHidden('firmar')
            ->assertActionHidden('rechazar');
    }

    public function test_el_creador_sin_permisos_generales_ve_su_envio(): void
    {
        $solicitud = $this->envioEnCurso($this->creador, [$this->firmanteConFirma('Juanito')]);

        $this->actingAs($this->creador)
            ->get(SolicitudFirmaResource::getUrl('view', ['record' => $solicitud], panel: 'admin'))
            ->assertSuccessful();
    }

    public function test_el_creador_cancela_y_un_tercero_no_puede(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito]);

        $this->actingAs($juanito);
        $this->pantalla($solicitud->id)->assertActionHidden('cancelar');

        $this->actingAs($this->creador);
        $this->pantalla($solicitud->id)
            ->callAction('cancelar', ['motivo' => 'Documento equivocado'])
            ->assertNotified('Envío cancelado');

        $this->assertSame(EstadoSolicitudFirma::Cancelada, $solicitud->refresh()->estado);
        $this->assertSame('Documento equivocado', $solicitud->motivo_cancelacion);
    }

    public function test_un_admin_con_permiso_puede_cancelar_envios_ajenos(): void
    {
        $solicitud = $this->envioEnCurso($this->creador, [$this->firmanteConFirma('Juanito')]);
        $rol = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $rol->givePermissionTo([
            Permission::firstOrCreate(['name' => 'Cancel:SolicitudFirma', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'View:SolicitudFirma', 'guard_name' => 'web']),
            Permission::firstOrCreate(['name' => 'ViewAny:SolicitudFirma', 'guard_name' => 'web']),
        ]);
        $admin = User::factory()->create();
        $admin->assignRole($rol);
        $this->actingAs($admin);

        $this->pantalla($solicitud->id)
            ->callAction('cancelar', ['motivo' => 'Pedido de gerencia'])
            ->assertNotified('Envío cancelado');
    }

    public function test_el_creador_reemplaza_un_firmante_y_quita_el_orden(): void
    {
        $pedro = $this->firmanteConFirma('Pedro');
        $carlos = $this->firmanteConFirma('Carlos');
        $ana = $this->firmanteConFirma('Ana');
        $solicitud = $this->envioEnCurso($this->creador, [$pedro, $carlos], ordenObligatorio: true);
        $this->actingAs($this->creador);

        $this->pantalla($solicitud->id)
            ->callAction('reemplazarFirmante', [
                'firmante' => $solicitud->firmantes()->where('usuario_id', $pedro->id)->sole()->id,
                'nuevo' => $ana->id,
            ])
            ->assertNotified('Firmante reemplazado');

        $this->assertSame([$ana->id, $carlos->id], $solicitud->firmantes()->pluck('usuario_id')->all());

        $this->pantalla($solicitud->id)
            ->callAction('quitarOrden')
            ->assertNotified('Ahora se firma en cualquier orden');

        $this->assertFalse($solicitud->refresh()->orden_obligatorio);
    }

    public function test_descargar_todos_los_documentos_en_un_zip(): void
    {
        $juanito = $this->firmanteConFirma('Juanito');
        $solicitud = $this->envioEnCurso($this->creador, [$juanito], documentos: 2);
        $this->actingAs($juanito);

        $respuesta = $this->get(route('firma.documentos.zip', $solicitud));

        $respuesta->assertSuccessful()->assertDownload("{$solicitud->codigo}.zip");

        $zip = new ZipArchive;
        $zip->open($respuesta->getFile()->getPathname());
        $this->assertSame(2, $zip->numFiles);
        $zip->close();

        $this->actingAs($this->firmanteConFirma('Ajeno'))
            ->get(route('firma.documentos.zip', $solicitud))
            ->assertForbidden();
    }
}
