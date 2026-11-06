<?php

namespace Tests\Unit\Enums;

use App\Enums\DominioActividad;
use App\Models\AppearanceSetting;
use App\Models\Concerns\LogsIcuActivity;
use App\Models\Equipo;
use App\Models\User;
use App\Models\UsuarioFortitoken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

class DominioActividadTest extends TestCase
{
    /**
     * @return list<class-string<Model>>
     */
    private function modelosQueRegistranActividad(): array
    {
        $modelos = [];

        foreach (Finder::create()->files()->in(base_path('app/Models'))->name('*.php') as $archivo) {
            /** @var SplFileInfo $archivo */
            $clase = 'App\\Models\\'.Str::of($archivo->getRelativePathname())
                ->replace(['/', '.php'], ['\\', ''])
                ->toString();

            if (! class_exists($clase)) {
                continue;
            }

            if (! is_subclass_of($clase, Model::class)) {
                continue;
            }

            if (! in_array(LogsIcuActivity::class, class_uses_recursive($clase), true)) {
                continue;
            }

            $modelos[] = $clase;
        }

        return $modelos;
    }

    public function test_todos_los_modelos_auditados_tienen_un_dominio_asignado(): void
    {
        $sinDominio = array_values(array_filter(
            $this->modelosQueRegistranActividad(),
            fn (string $modelo): bool => DominioActividad::paraModelo($modelo) === null,
        ));

        $this->assertSame([], $sinDominio, 'Estos modelos usan LogsIcuActivity pero no están en DominioActividad::mapaDeModelos().');
    }

    public function test_el_mapa_no_tiene_entradas_obsoletas(): void
    {
        $auditados = $this->modelosQueRegistranActividad();

        $obsoletos = array_values(array_filter(
            array_keys(DominioActividad::mapaDeModelos()),
            fn (string $modelo): bool => ! in_array($modelo, $auditados, true),
        ));

        $this->assertSame([], $obsoletos, 'Estas entradas del mapa apuntan a modelos que ya no registran actividad.');
    }

    public function test_los_dominios_transversales_no_estan_en_el_mapa_de_modelos(): void
    {
        $mapa = DominioActividad::mapaDeModelos();

        // Seguridad y Configuración se escriben a mano desde App\Support\Auditoria:
        // mapearlos obligaría a poner LogsIcuActivity en los modelos de settings, y
        // Marca::mount() crea su fila en un GET, generando registros espurios.
        // Única excepción: los FortiToken, credenciales que se gestionan en Seguridad.
        $this->assertNotContains(DominioActividad::Configuracion, array_values($mapa));
        $this->assertSame(
            [UsuarioFortitoken::class],
            array_keys(array_filter($mapa, fn (DominioActividad $dominio): bool => $dominio === DominioActividad::Seguridad)),
        );
    }

    public function test_cada_dominio_tiene_etiqueta_y_color(): void
    {
        foreach (DominioActividad::cases() as $dominio) {
            $this->assertNotSame('', $dominio->getLabel());
            $this->assertNotSame('', $dominio->getColor());
        }
    }

    public function test_resuelve_el_dominio_desde_una_instancia_o_desde_el_nombre_de_clase(): void
    {
        $this->assertSame(DominioActividad::Usuarios, DominioActividad::paraModelo(User::class));
        $this->assertSame(DominioActividad::Equipos, DominioActividad::paraModelo(new Equipo));
        $this->assertNull(DominioActividad::paraModelo(AppearanceSetting::class));
    }
}
