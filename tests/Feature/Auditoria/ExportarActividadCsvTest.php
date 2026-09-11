<?php

namespace Tests\Feature\Auditoria;

use App\Enums\EventoSeguridad;
use App\Filament\Resources\Activitylogs\Pages\ListActivitylogs;
use App\Models\Area;
use App\Models\User;
use App\Support\Auditoria\AuditorSeguridad;
use App\Support\Auditoria\ExportadorActividadCsv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ExportarActividadCsvTest extends TestCase
{
    use RefreshDatabase;

    protected User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::before(fn () => true);

        $this->usuario = User::factory()->create(['name' => 'Deyvid Vladimir']);

        $this->actingAs($this->usuario);
    }

    private function auditor(): AuditorSeguridad
    {
        return app(AuditorSeguridad::class);
    }

    private function exportador(): ExportadorActividadCsv
    {
        return app(ExportadorActividadCsv::class);
    }

    private function ultimo(): Activity
    {
        return Activity::query()->latest('id')->firstOrFail();
    }

    /**
     * Pulsa la acción y devuelve el CSV que Livewire entrega al navegador.
     */
    private function exportar(): string
    {
        $componente = Livewire::test(ListActivitylogs::class)->callAction('exportarCsv');

        $descarga = $componente->effects['download'] ?? null;

        $this->assertNotNull($descarga, 'La acción no produjo ninguna descarga.');
        $this->assertStringEndsWith('.csv', $descarga['name']);

        return base64_decode($descarga['content']);
    }

    public function test_la_accion_de_exportar_existe_en_el_listado(): void
    {
        Livewire::test(ListActivitylogs::class)
            ->assertActionExists('exportarCsv');
    }

    public function test_el_csv_abre_con_bom_para_que_excel_respete_los_acentos(): void
    {
        $this->auditor()->ingresoExitoso($this->usuario);

        $csv = $this->exportar();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function test_exportar_sin_registros_avisa_y_no_descarga(): void
    {
        // El propio User de setUp() deja su registro de creación.
        Activity::query()->delete();

        $componente = Livewire::test(ListActivitylogs::class)
            ->callAction('exportarCsv')
            ->assertNotified();

        $this->assertArrayNotHasKey('download', $componente->effects);
    }

    public function test_las_siete_columnas_consolidadas_en_el_orden_pedido(): void
    {
        $this->assertSame([
            'Fecha',
            'Categoría',
            'Acción / resultado',
            'Actor / origen',
            'Sujeto',
            'Descripción',
            'Detalle consolidado',
        ], $this->exportador()->cabeceras());
    }

    public function test_cada_evento_es_una_sola_fila_del_csv(): void
    {
        // Aislado: crear un User en cascada también genera Área y Cargo (cada
        // uno con su propio evento), así que se cuenta lo que realmente hay
        // en vez de asumir un número fijo.
        Activity::query()->delete();

        $this->auditor()->ingresoExitoso($this->usuario);
        Area::factory()->create();

        $totalEventos = Activity::query()->count();

        $csv = $this->exportar();

        // Se valida con el parser real de PHP (fgetcsv), no con substrings:
        // si un salto de línea interno de «Detalle consolidado» fragmentara
        // el CSV en filas de más, este conteo lo detectaría.
        $recurso = fopen('php://temp', 'r+');
        fwrite($recurso, $csv);
        rewind($recurso);

        $filas = [];

        while (($fila = fgetcsv($recurso, separator: ';', escape: '')) !== false) {
            $filas[] = $fila;
        }

        fclose($recurso);

        $this->assertCount($totalEventos + 1, $filas, 'Cabecera + cada evento debe mapear 1 a 1, sin fragmentarse.');
    }

    public function test_usa_punto_y_coma_como_separador(): void
    {
        $csv = $this->exportador()->cabeceras();

        // No se prueba contra el CSV completo (los valores pueden traer comas
        // dentro de una celda entre comillas), sino contra la línea servida.
        $linea = implode(';', $csv);

        $this->assertSame('Fecha;Categoría;Acción / resultado;Actor / origen;Sujeto;Descripción;Detalle consolidado', $linea);
    }

    public function test_accion_resultado_combina_evento_y_desenlace(): void
    {
        $this->auditor()->ingresoExitoso($this->usuario);

        $fila = $this->exportador()->fila($this->ultimo());

        $this->assertSame(
            EventoSeguridad::IngresoExitoso->getLabel().' · '.EventoSeguridad::IngresoExitoso->resultado()->getLabel(),
            $fila[2],
        );
    }

    public function test_accion_resultado_omite_el_desenlace_en_cambios_de_datos(): void
    {
        Area::factory()->create();

        $fila = $this->exportador()->fila($this->ultimo());

        $this->assertSame('Creado', $fila[2]);
    }

    public function test_actor_origen_apila_usuario_correo_e_ip_sin_lineas_vacias(): void
    {
        $this->auditor()->ingresoExitoso($this->usuario);

        $fila = $this->exportador()->fila($this->ultimo());
        $lineas = explode("\n", $fila[3]);

        $this->assertSame($this->usuario->name, $lineas[0]);
        $this->assertSame($this->usuario->email, $lineas[1]);
        $this->assertNotSame('', $lineas[2] ?? null);
    }

    public function test_actor_origen_omite_la_linea_de_usuario_si_no_hay_causante(): void
    {
        $this->auditor()->correoDesconocido('intruso@example.com');

        $fila = $this->exportador()->fila($this->ultimo());
        $lineas = explode("\n", $fila[3]);

        // Sin causante no hay línea de usuario, pero sí las de correo e IP.
        $this->assertSame('intruso@example.com', $lineas[0]);
        $this->assertCount(2, $lineas);
    }

    public function test_detalle_consolidado_agrupa_cambios_y_detalle_bajo_encabezados(): void
    {
        $area = Area::factory()->create(['area' => 'Nombre viejo']);
        $area->update(['area' => 'Nombre nuevo']);

        $fila = $this->exportador()->fila($this->ultimo());

        $this->assertStringContainsString("Cambios:\n", $fila[6]);
        $this->assertStringContainsString('Nombre viejo → Nombre nuevo', $fila[6]);
    }

    public function test_un_alta_no_muestra_la_flecha_desde_un_valor_vacio(): void
    {
        Area::factory()->create(['area' => 'Tecnologia']);

        $fila = $this->exportador()->fila($this->ultimo());

        $this->assertStringContainsString('Tecnologia', $fila[6]);
        $this->assertStringNotContainsString('(vacío) →', $fila[6]);
    }

    public function test_el_detalle_consolidado_agrupa_las_propiedades_sueltas(): void
    {
        $this->auditor()->passwordIncorrecta($this->usuario, 2);

        $fila = $this->exportador()->fila($this->ultimo());

        $this->assertStringContainsString("Detalle:\n", $fila[6]);
        $this->assertStringContainsString('intentos restantes: 2', $fila[6]);
    }

    public function test_el_csv_nunca_expone_contrasenas(): void
    {
        $this->auditor()->registrar(EventoSeguridad::OtpIncorrecto, sujeto: $this->usuario, propiedades: [
            'password' => 'SuperSecreta123',
            'intentos_restantes' => 2,
        ]);

        $csv = $this->exportar();

        $this->assertStringNotContainsString('SuperSecreta123', $csv);
        $this->assertStringContainsString('intentos restantes', $csv);
    }
}
