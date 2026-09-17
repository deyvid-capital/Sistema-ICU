<?php

namespace Tests\Feature\Filament;

use App\Enums\EstadoAprobacionExcepcion;
use App\Filament\Resources\SoftwareExcs\Pages\ListSoftwareExcs;
use App\Filament\Resources\SoftwareExcs\Pages\ViewSoftwareExc;
use App\Filament\Resources\SoftwareExcs\SoftwareExcResource;
use App\Models\Area;
use App\Models\Software;
use App\Models\SoftwareExc;
use App\Models\User;
use App\Notifications\CriticalEventNotification;
use App\Services\Software\DecidirExcepcionSoftware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AprobarExcepcionSoftwareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('View:SoftwareExc', 'web');
        Permission::findOrCreate('ViewAny:SoftwareExc', 'web');
    }

    /**
     * @return array{area: Area, aprobador: User, usuario: User, software: Software, excepcion: SoftwareExc}
     */
    private function excepcionPendiente(?User $aprobador = null): array
    {
        $area = Area::factory()->create();
        $aprobador ??= User::factory()->conRol('operador')->create([
            'name' => 'Gerente de Riesgo',
            'id_area' => $area->id_area,
        ]);
        $usuario = User::factory()->create(['name' => 'Colaborador']);
        $software = Software::factory()->create();

        $excepcion = SoftwareExc::factory()->create([
            'usuario_id' => $usuario->id,
            'software_id' => $software->id_software,
            'area_aprobadora_id' => $area->id_area,
            'aprobador_id' => $aprobador->id,
            'estado_aprobacion' => EstadoAprobacionExcepcion::Pendiente,
        ]);

        return compact('area', 'aprobador', 'usuario', 'software', 'excepcion');
    }

    public function test_al_crear_queda_pendiente_y_notifica_al_aprobador_por_campana_y_correo(): void
    {
        Notification::fake();

        ['aprobador' => $aprobador, 'excepcion' => $excepcion] = $this->excepcionPendiente();

        $this->assertTrue($excepcion->estaPendiente());

        Notification::assertSentTo(
            $aprobador,
            CriticalEventNotification::class,
            function (CriticalEventNotification $notification) use ($excepcion): bool {
                $this->assertSame(CriticalEventNotification::SEVERIDAD_ALTA, $notification->severity);
                $this->assertNotNull($notification->url);
                $this->assertStringContainsString((string) $excepcion->getKey(), $notification->url);
                $this->assertEqualsCanonicalizing(['database', 'mail'], $notification->via($excepcion->aprobador));

                return true;
            },
        );
    }

    public function test_el_aprobador_puede_aprobar(): void
    {
        ['aprobador' => $aprobador, 'excepcion' => $excepcion] = $this->excepcionPendiente();

        $this->actingAs($aprobador);

        Livewire::test(ViewSoftwareExc::class, ['record' => $excepcion->getKey()])
            ->callAction('aprobarExcepcion')
            ->assertHasNoActionErrors()
            ->assertNotified();

        $excepcion->refresh();

        $this->assertSame(EstadoAprobacionExcepcion::Aprobada, $excepcion->estado_aprobacion);
        $this->assertSame($aprobador->id, $excepcion->decidido_por_id);
        $this->assertNotNull($excepcion->decidido_at);
    }

    public function test_otro_usuario_del_area_no_puede_aprobar(): void
    {
        ['area' => $area, 'excepcion' => $excepcion] = $this->excepcionPendiente();

        Permission::findOrCreate('View:SoftwareExc', 'web');
        Permission::findOrCreate('ViewAny:SoftwareExc', 'web');

        $otro = User::factory()->conRol('operador')->create(['id_area' => $area->id_area]);
        $otro->givePermissionTo(['View:SoftwareExc', 'ViewAny:SoftwareExc']);

        $this->actingAs($otro);

        Livewire::test(ViewSoftwareExc::class, ['record' => $excepcion->getKey()])
            ->assertActionHidden('aprobarExcepcion')
            ->assertActionHidden('rechazarExcepcion');

        $this->expectException(InvalidArgumentException::class);

        app(DecidirExcepcionSoftware::class)->aprobar($excepcion, $otro);
    }

    public function test_super_admin_puede_aprobar_solo_con_nota_de_intervencion(): void
    {
        ['excepcion' => $excepcion] = $this->excepcionPendiente();
        $superAdmin = User::factory()->conRol('super_admin')->create(['name' => 'Super Admin']);

        $this->actingAs($superAdmin);

        Livewire::test(ViewSoftwareExc::class, ['record' => $excepcion->getKey()])
            ->callAction('aprobarExcepcion')
            ->assertHasActionErrors(['comentario_decision']);

        $this->assertTrue($excepcion->fresh()->estaPendiente());

        Livewire::test(ViewSoftwareExc::class, ['record' => $excepcion->getKey()])
            ->callAction('aprobarExcepcion', [
                'comentario_decision' => 'La gerente de Riesgo está de vacaciones.',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified();

        $excepcion->refresh();

        $this->assertSame(EstadoAprobacionExcepcion::Aprobada, $excepcion->estado_aprobacion);
        $this->assertSame($superAdmin->id, $excepcion->decidido_por_id);
        $this->assertSame('La gerente de Riesgo está de vacaciones.', $excepcion->comentario_decision);
    }

    public function test_super_admin_que_es_el_designado_puede_aprobar_sin_nota(): void
    {
        $area = Area::factory()->create();
        $superAdmin = User::factory()->conRol('super_admin')->create(['id_area' => $area->id_area]);
        ['excepcion' => $excepcion] = $this->excepcionPendiente($superAdmin);

        $this->actingAs($superAdmin);

        Livewire::test(ViewSoftwareExc::class, ['record' => $excepcion->getKey()])
            ->callAction('aprobarExcepcion')
            ->assertHasNoActionErrors();

        $this->assertSame(EstadoAprobacionExcepcion::Aprobada, $excepcion->fresh()->estado_aprobacion);
        $this->assertNull($excepcion->fresh()->comentario_decision);
    }

    public function test_el_rechazo_exige_comentario_y_deja_el_estado_rechazada(): void
    {
        ['aprobador' => $aprobador, 'excepcion' => $excepcion] = $this->excepcionPendiente();

        $this->actingAs($aprobador);

        Livewire::test(ViewSoftwareExc::class, ['record' => $excepcion->getKey()])
            ->callAction('rechazarExcepcion', ['comentario_decision' => ''])
            ->assertHasActionErrors(['comentario_decision']);

        Livewire::test(ViewSoftwareExc::class, ['record' => $excepcion->getKey()])
            ->callAction('rechazarExcepcion', [
                'comentario_decision' => 'No se justifica el acceso.',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(EstadoAprobacionExcepcion::Rechazada, $excepcion->fresh()->estado_aprobacion);
        $this->assertSame('No se justifica el acceso.', $excepcion->fresh()->comentario_decision);
    }

    public function test_se_puede_cambiar_area_y_aprobador_en_pendiente(): void
    {
        Notification::fake();

        ['excepcion' => $excepcion] = $this->excepcionPendiente();
        $otraArea = Area::factory()->create();
        $nuevoAprobador = User::factory()->conRol('operador')->create([
            'name' => 'Gerente de Cumplimiento',
            'id_area' => $otraArea->id_area,
        ]);

        $excepcion->update([
            'area_aprobadora_id' => $otraArea->id_area,
            'aprobador_id' => $nuevoAprobador->id,
        ]);

        $excepcion->refresh();

        $this->assertSame($otraArea->id_area, $excepcion->area_aprobadora_id);
        $this->assertSame($nuevoAprobador->id, $excepcion->aprobador_id);

        Notification::assertSentTo($nuevoAprobador, CriticalEventNotification::class);
    }

    public function test_no_se_puede_cambiar_aprobador_cuando_ya_fue_decidida(): void
    {
        ['aprobador' => $aprobador, 'area' => $area, 'excepcion' => $excepcion] = $this->excepcionPendiente();

        app(DecidirExcepcionSoftware::class)->aprobar($excepcion, $aprobador);

        $otro = User::factory()->conRol('operador')->create(['id_area' => $area->id_area]);

        $excepcion->refresh()->update(['aprobador_id' => $otro->id]);

        $this->assertSame($aprobador->id, $excepcion->fresh()->aprobador_id);
    }

    public function test_el_listado_oculta_excepciones_ajenas_al_aprobador_sin_view_any(): void
    {
        Permission::findOrCreate('ViewAny:SoftwareExc', 'web');

        ['aprobador' => $aprobador, 'excepcion' => $mia] = $this->excepcionPendiente();
        ['excepcion' => $ajena] = $this->excepcionPendiente();

        $this->actingAs($aprobador);

        Livewire::test(ListSoftwareExcs::class)
            ->assertCanSeeTableRecords([$mia])
            ->assertCanNotSeeTableRecords([$ajena]);

        $this->assertTrue(
            SoftwareExcResource::getEloquentQuery()
                ->whereKey($mia->getKey())
                ->exists(),
        );
        $this->assertFalse(
            SoftwareExcResource::getEloquentQuery()
                ->whereKey($ajena->getKey())
                ->exists(),
        );
    }

    public function test_la_intervencion_notifica_al_aprobador_original_por_campana_y_correo(): void
    {
        Notification::fake();

        ['aprobador' => $aprobador, 'excepcion' => $excepcion] = $this->excepcionPendiente();
        $superAdmin = User::factory()->conRol('super_admin')->create();

        app(DecidirExcepcionSoftware::class)->aprobar(
            $excepcion,
            $superAdmin,
            'La gerente no está disponible esta semana.',
        );

        Notification::assertSentTo(
            $aprobador,
            CriticalEventNotification::class,
            function (CriticalEventNotification $notification) use ($aprobador): bool {
                $this->assertSame(CriticalEventNotification::SEVERIDAD_ALTA, $notification->severity);
                $this->assertEqualsCanonicalizing(['database', 'mail'], $notification->via($aprobador));

                return str_contains($notification->body, 'Super Admin intervino');
            },
        );
    }
}
