<?php

namespace Tests\Feature\Firma;

use App\Enums\EstadoCertificadoFirma;
use App\Filament\Clusters\Configuracion\Pages\FirmaDigital;
use App\Filament\Resources\CertificadosFirma\CertificadoFirmaResource;
use App\Filament\Resources\CertificadosFirma\Pages\ListCertificadosFirma;
use App\Models\Cargo;
use App\Models\CertificadoFirma;
use App\Models\FirmaSetting;
use App\Models\User;
use App\Notifications\Firma\FirmaAsignadaNotification;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\Firma\Concerns\FakeFirmador;
use Tests\TestCase;

class CertificadoFirmaResourceTest extends TestCase
{
    use FakeFirmador;
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();
        Gate::before(fn () => true);

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
        $this->actingAs($this->admin);
    }

    public function test_la_lista_muestra_las_firmas_asignadas(): void
    {
        $activo = $this->certificadoActivo($this->titular());
        $pendiente = $this->certificadoPendiente($this->titular());

        $this->get(CertificadoFirmaResource::getUrl(panel: 'admin'))->assertSuccessful();

        Livewire::test(ListCertificadosFirma::class)
            ->assertCanSeeTableRecords([$activo, $pendiente]);
    }

    public function test_el_historial_se_oculta_por_defecto(): void
    {
        $reemplazado = CertificadoFirma::factory()->create(['estado' => EstadoCertificadoFirma::Reemplazado]);

        Livewire::test(ListCertificadosFirma::class)->assertCanNotSeeTableRecords([$reemplazado]);
    }

    public function test_asignar_firma_sube_el_p12_cifrado_la_activa_y_avisa_al_titular(): void
    {
        $this->fakeFirmador();
        $cargo = Cargo::factory()->create(['cargo' => 'Oficial de Infraestructura']);
        $titular = $this->titular(['nombres' => 'Vladimir Adalid', 'apellidos' => 'Calderón Sarmiento', 'id_cargo' => $cargo->id_cargo]);

        Livewire::test(ListCertificadosFirma::class)
            ->mountAction('asignar')
            ->fillForm(['usuario_id' => $titular->id])
            ->assertSchemaStateSet([
                'nombre_en_sello' => 'Vladimir Adalid Calderón Sarmiento',
                'cargo_en_sello' => 'Oficial de Infraestructura',
            ])
            ->fillForm([
                'p12' => UploadedFile::fake()->createWithContent('firma.p12', self::CONTENIDO_P12),
                'contrasena_p12' => self::CONTRASENA_P12,
            ])
            ->callMountedAction()
            ->assertHasNoFormErrors()
            ->assertNotified('Firma asignada y activada');

        $certificado = $titular->certificadosFirma()->sole();
        $this->assertTrue($certificado->estaPendienteDeVincular());
        $this->assertSame('Vladimir Adalid Calderón Sarmiento', $certificado->nombre_en_sello);
        $this->assertSame($this->admin->id, $certificado->cargado_por);
        Notification::assertSentTo($titular, FirmaAsignadaNotification::class);
    }

    public function test_asignar_un_archivo_invalido_muestra_el_error(): void
    {
        $this->fakeFirmador(p12Valido: false);
        $titular = $this->titular();

        Livewire::test(ListCertificadosFirma::class)
            ->callAction('asignar', [
                'usuario_id' => $titular->id,
                'p12' => UploadedFile::fake()->createWithContent('firma.p12', 'basura'),
                'contrasena_p12' => self::CONTRASENA_P12,
                'nombre_en_sello' => 'X',
            ])
            ->assertNotified('No se pudo cargar la firma');

        $this->assertDatabaseCount('certificados_firma', 0);
    }

    public function test_asignar_con_contrasena_del_p12_incorrecta_muestra_el_error(): void
    {
        $this->fakeFirmador();

        Livewire::test(ListCertificadosFirma::class)
            ->callAction('asignar', [
                'usuario_id' => $this->titular()->id,
                'p12' => UploadedFile::fake()->createWithContent('firma.p12', self::CONTENIDO_P12),
                'contrasena_p12' => 'otra',
                'nombre_en_sello' => 'X',
            ])
            ->assertNotified('No se pudo cargar la firma');

        $this->assertDatabaseCount('certificados_firma', 0);
    }

    public function test_asignar_exige_la_contrasena_del_p12(): void
    {
        $this->fakeFirmador();

        Livewire::test(ListCertificadosFirma::class)
            ->callAction('asignar', [
                'usuario_id' => $this->titular()->id,
                'p12' => UploadedFile::fake()->createWithContent('firma.p12', self::CONTENIDO_P12),
                'nombre_en_sello' => 'X',
            ])
            ->assertHasFormErrors(['contrasena_p12' => 'required']);
    }

    public function test_exige_extension_p12_o_pfx(): void
    {
        $this->fakeFirmador();

        Livewire::test(ListCertificadosFirma::class)
            ->callAction('asignar', [
                'usuario_id' => $this->titular()->id,
                'p12' => UploadedFile::fake()->createWithContent('firma.pdf', '%PDF'),
                'nombre_en_sello' => 'X',
            ])
            ->assertHasFormErrors(['p12']);
    }

    public function test_el_selector_excluye_usuarios_que_ya_tienen_firma(): void
    {
        $this->fakeFirmador();
        $conFirma = $this->titular(['name' => 'Con Firma']);
        $this->certificadoActivo($conFirma);

        Livewire::test(ListCertificadosFirma::class)
            ->callAction('asignar', [
                'usuario_id' => $conFirma->id,
                'p12' => UploadedFile::fake()->createWithContent('firma.p12', self::CONTENIDO_P12),
                'nombre_en_sello' => 'X',
            ])
            ->assertHasFormErrors(['usuario_id']);

        $this->assertSame(1, $conFirma->certificadosFirma()->count());
    }

    public function test_renovar_activa_el_nuevo_p12_y_reemplaza_al_actual(): void
    {
        $this->fakeFirmador();
        $actual = $this->certificadoActivo($this->titular(), ['huella_sha256' => str_repeat('b', 64)]);

        Livewire::test(ListCertificadosFirma::class)
            ->callAction(TestAction::make('renovar')->table($actual), [
                'p12' => UploadedFile::fake()->createWithContent('nuevo.p12', 'p12-2027'),
                'contrasena_p12' => self::CONTRASENA_P12,
            ])
            ->assertNotified('Firma renovada');

        $this->assertSame(EstadoCertificadoFirma::Reemplazado, $actual->refresh()->estado);
        $this->assertSame($actual->id, CertificadoFirma::query()->where('estado', EstadoCertificadoFirma::Activo)->sole()->reemplaza_a_id);
    }

    public function test_activar_solo_aparece_para_firmas_sin_contrasena_en_custodia(): void
    {
        $this->fakeFirmador();
        $pendiente = $this->certificadoPendiente($this->titular());
        $activo = $this->certificadoActivo($this->titular(), ['huella_sha256' => str_repeat('b', 64)]);

        Livewire::test(ListCertificadosFirma::class)
            ->assertActionHidden(TestAction::make('activar')->table($activo))
            ->assertActionVisible(TestAction::make('activar')->table($pendiente))
            ->callAction(TestAction::make('activar')->table($pendiente), ['contrasena_p12' => self::CONTRASENA_P12])
            ->assertNotified('Firma activada');

        $this->assertTrue($pendiente->refresh()->estaPendienteDeVincular());
        Notification::assertSentTo($pendiente->usuario, FirmaAsignadaNotification::class);
    }

    public function test_editar_los_datos_del_sello(): void
    {
        $certificado = $this->certificadoActivo($this->titular());

        Livewire::test(ListCertificadosFirma::class)
            ->callAction(TestAction::make('editarSello')->table($certificado), [
                'nombre_en_sello' => 'Vladimir Adalid Calderón Sarmiento',
                'cargo_en_sello' => 'Oficial de Infraestructura',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('Vladimir Adalid Calderón Sarmiento', $certificado->refresh()->nombre_en_sello);
    }

    public function test_eliminar_la_asignacion_borra_el_archivo_y_deja_historial(): void
    {
        $certificado = $this->certificadoActivo($this->titular());
        $archivo = $certificado->archivo;

        Livewire::test(ListCertificadosFirma::class)
            ->callAction(TestAction::make('revocar')->table($certificado))
            ->assertNotified('Firma digital eliminada');

        $certificado->refresh();
        $this->assertSame(EstadoCertificadoFirma::Revocado, $certificado->estado);
        $this->assertNull($certificado->contrasena_p12_envuelta);
        $this->assertNull($certificado->contrasena_p12_custodia);
        Storage::disk('local')->assertMissing($archivo);
    }

    public function test_reenviar_aviso_solo_para_firmas_sin_vincular(): void
    {
        $pendiente = $this->certificadoPendienteDeVincular($this->titular());
        $activo = $this->certificadoActivo($this->titular());

        Livewire::test(ListCertificadosFirma::class)
            ->assertActionVisible(TestAction::make('reenviarAviso')->table($pendiente))
            ->assertActionHidden(TestAction::make('reenviarAviso')->table($activo))
            ->callAction(TestAction::make('reenviarAviso')->table($pendiente));

        Notification::assertSentTo($pendiente->usuario, FirmaAsignadaNotification::class);
    }

    public function test_guardar_ajustes_de_firma_digital(): void
    {
        $this->get(FirmaDigital::getUrl(panel: 'admin'))->assertSuccessful();

        Livewire::test(FirmaDigital::class)
            ->assertSchemaStateSet(['requiere_2fa_al_firmar' => false, 'intentos_maximos' => 5])
            ->fillForm([
                'requiere_2fa_al_firmar' => true,
                'intentos_maximos' => 3,
                'minutos_bloqueo' => 30,
                'plazo_dias_por_defecto' => 5,
                'recordatorio_horas_por_defecto' => 12,
            ])
            ->call('guardar')
            ->assertHasNoFormErrors();

        $ajustes = FirmaSetting::actual();
        $this->assertTrue($ajustes->requiere_2fa_al_firmar);
        $this->assertSame(3, $ajustes->intentos_maximos);
        $this->assertSame(12, $ajustes->recordatorio_horas_por_defecto);
    }
}
