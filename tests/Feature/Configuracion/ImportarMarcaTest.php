<?php

namespace Tests\Feature\Configuracion;

use App\Models\AppearanceSetting;
use CapitalSafi\Apariencia\Settings\StudioSettings;
use CapitalSafi\Apariencia\Support\StudioBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ImportarMarcaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        StudioBrand::flush();
    }

    private function ajustes(): StudioSettings
    {
        return app(StudioSettings::class)->refresh();
    }

    /**
     * @param  array<string, mixed>  $atributos
     */
    private function marcaAntigua(array $atributos = []): AppearanceSetting
    {
        return AppearanceSetting::create(array_merge(['tema' => 'safi'], $atributos));
    }

    private function imagenHeredada(string $ruta, int $ancho = 300, int $alto = 120): void
    {
        Storage::disk('public')->put($ruta, UploadedFile::fake()->image('origen.png', $ancho, $alto)->getContent());
    }

    /**
     * @return list<string>
     */
    private function archivosDelPlugin(): array
    {
        return Storage::disk('public')->allFiles('apariencia/marca');
    }

    public function test_importa_logos_y_favicon_desde_media_library_y_los_recodifica(): void
    {
        $marca = $this->marcaAntigua(['alto_logo' => '3rem']);
        $marca->addMedia(UploadedFile::fake()->image('claro.png', 300, 120))->toMediaCollection('logo_claro');
        $marca->addMedia(UploadedFile::fake()->image('oscuro.png', 300, 120))->toMediaCollection('logo_oscuro');
        $marca->addMedia(UploadedFile::fake()->image('icono.png', 256, 256))->toMediaCollection('favicon');

        $this->artisan('apariencia:importar-marca')->assertSuccessful();

        $ajustes = $this->ajustes();

        $this->assertMatchesRegularExpression('#^apariencia/marca/logo-[0-9A-Za-z]+\.webp$#', (string) $ajustes->logo_light);
        $this->assertMatchesRegularExpression('#^apariencia/marca/logo-[0-9A-Za-z]+\.webp$#', (string) $ajustes->logo_dark);
        $this->assertMatchesRegularExpression('#^apariencia/marca/favicon-[0-9A-Za-z]+\.png$#', (string) $ajustes->favicon);
        $this->assertMatchesRegularExpression('#^apariencia/marca/favicon-touch-[0-9A-Za-z]+\.png$#', (string) $ajustes->favicon_touch);
        $this->assertSame('3rem', $ajustes->logo_height);

        foreach ([$ajustes->logo_light, $ajustes->logo_dark, $ajustes->favicon, $ajustes->favicon_touch] as $ruta) {
            Storage::disk('public')->assertExists($ruta);
        }

        // La marca antigua queda intacta.
        $this->assertTrue($marca->fresh()->hasMedia('logo_claro'));
    }

    public function test_importa_desde_las_columnas_heredadas_si_no_hay_media(): void
    {
        $this->imagenHeredada('apariencia/logo-claro.png');
        $this->imagenHeredada('apariencia/favicon.png', 256, 256);

        $this->marcaAntigua(['logo_claro' => 'apariencia/logo-claro.png', 'favicon' => 'apariencia/favicon.png']);

        $this->artisan('apariencia:importar-marca')->assertSuccessful();

        $ajustes = $this->ajustes();

        $this->assertNotEmpty($ajustes->logo_light);
        $this->assertNull($ajustes->logo_dark);
        $this->assertNotEmpty($ajustes->favicon);
        $this->assertNotEmpty($ajustes->favicon_touch);

        // El archivo antiguo no se borra.
        Storage::disk('public')->assertExists('apariencia/logo-claro.png');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function placasProvider(): array
    {
        return [
            'auto' => ['auto', 'auto'],
            'claro' => ['claro', 'always'],
            'ninguno' => ['ninguno', 'never'],
        ];
    }

    #[DataProvider('placasProvider')]
    public function test_mapea_fondo_logo_a_logo_plate(string $fondoAntiguo, string $placaEsperada): void
    {
        $this->marcaAntigua(['fondo_logo' => $fondoAntiguo]);

        $this->artisan('apariencia:importar-marca')->assertSuccessful();

        $this->assertSame($placaEsperada, $this->ajustes()->logo_plate);
    }

    public function test_un_alto_invalido_no_se_importa(): void
    {
        $this->marcaAntigua(['alto_logo' => 'enorme']);

        $this->artisan('apariencia:importar-marca')
            ->expectsOutputToContain('no es un valor válido')
            ->assertSuccessful();

        $this->assertSame('2.5rem', $this->ajustes()->logo_height);
    }

    public function test_salta_svg_e_ico_con_aviso_y_deja_el_respaldo(): void
    {
        Storage::disk('public')->put('apariencia/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        Storage::disk('public')->put('apariencia/favicon.ico', 'ico');

        $this->marcaAntigua(['logo_claro' => 'apariencia/logo.svg', 'favicon' => 'apariencia/favicon.ico']);

        $this->artisan('apariencia:importar-marca')
            ->expectsOutputToContain('logo.svg')
            ->expectsOutputToContain('favicon.ico')
            ->assertSuccessful();

        $ajustes = $this->ajustes();

        $this->assertNull($ajustes->logo_light);
        $this->assertNull($ajustes->favicon);
        $this->assertSame([], $this->archivosDelPlugin());
    }

    public function test_un_archivo_que_no_es_imagen_se_omite_con_aviso(): void
    {
        Storage::disk('public')->put('apariencia/falso.png', '<?php echo "no soy una imagen";');

        $this->marcaAntigua(['logo_claro' => 'apariencia/falso.png']);

        $this->artisan('apariencia:importar-marca')
            ->expectsOutputToContain('no se importa')
            ->assertSuccessful();

        $this->assertNull($this->ajustes()->logo_light);
        $this->assertSame([], $this->archivosDelPlugin());
    }

    public function test_un_favicon_no_cuadrado_o_pequeno_se_omite(): void
    {
        $this->imagenHeredada('apariencia/favicon.png', 32, 32);

        $this->marcaAntigua(['favicon' => 'apariencia/favicon.png']);

        $this->artisan('apariencia:importar-marca')
            ->expectsOutputToContain('Favicon')
            ->assertSuccessful();

        $this->assertNull($this->ajustes()->favicon);
    }

    public function test_dry_run_no_escribe_nada(): void
    {
        $this->imagenHeredada('apariencia/logo-claro.png');

        $this->marcaAntigua([
            'tema' => 'turquesa',
            'logo_claro' => 'apariencia/logo-claro.png',
            'alto_logo' => '4rem',
            'fondo_logo' => 'claro',
        ]);

        $antes = $this->ajustes()->toArray();

        $this->artisan('apariencia:importar-marca', ['--dry-run' => true])
            ->expectsOutputToContain('dry-run')
            ->assertSuccessful();

        $this->assertSame($antes, $this->ajustes()->toArray());
        $this->assertSame([], $this->archivosDelPlugin());
    }

    public function test_una_segunda_ejecucion_no_cambia_nada(): void
    {
        $this->imagenHeredada('apariencia/logo-claro.png');

        $this->marcaAntigua(['logo_claro' => 'apariencia/logo-claro.png', 'fondo_logo' => 'claro']);

        $this->artisan('apariencia:importar-marca')->assertSuccessful();

        $primera = $this->ajustes()->toArray();
        $archivos = $this->archivosDelPlugin();

        $this->artisan('apariencia:importar-marca')
            ->expectsOutputToContain('ya tiene')
            ->assertSuccessful();

        $this->assertSame($primera, $this->ajustes()->toArray());
        $this->assertSame($archivos, $this->archivosDelPlugin());
    }

    public function test_force_reimporta_y_borra_los_archivos_reemplazados(): void
    {
        $this->imagenHeredada('apariencia/logo-claro.png');

        $this->marcaAntigua(['logo_claro' => 'apariencia/logo-claro.png']);

        $this->artisan('apariencia:importar-marca')->assertSuccessful();

        $primero = $this->ajustes()->logo_light;

        $this->artisan('apariencia:importar-marca', ['--force' => true])->assertSuccessful();

        $segundo = $this->ajustes()->logo_light;

        $this->assertNotSame($primero, $segundo);
        Storage::disk('public')->assertMissing($primero);
        Storage::disk('public')->assertExists($segundo);
        $this->assertCount(1, $this->archivosDelPlugin());
    }

    public function test_aplica_el_tema_antiguo_si_existe_en_el_plugin(): void
    {
        $this->marcaAntigua(['tema' => 'turquesa']);

        $this->artisan('apariencia:importar-marca')->assertSuccessful();

        $ajustes = $this->ajustes();

        $this->assertSame('turquesa', $ajustes->theme);
        $this->assertSame(config('apariencia.themes.turquesa.light.primary'), $ajustes->primary);
    }

    public function test_no_importa_el_tema_personalizado(): void
    {
        $antes = $this->ajustes()->theme;

        $this->marcaAntigua(['tema' => 'personalizado', 'color_primario' => '#112233']);

        $this->artisan('apariencia:importar-marca')->assertSuccessful();

        $this->assertSame($antes, $this->ajustes()->theme);
        $this->assertNotSame('#112233', $this->ajustes()->primary);
    }

    public function test_no_pisa_un_tema_elegido_despues_en_el_plugin(): void
    {
        $ajustes = $this->ajustes();
        $ajustes->theme = 'neutro';
        $ajustes->save();

        $this->marcaAntigua(['tema' => 'turquesa', 'fondo_logo' => 'ninguno']);

        $this->artisan('apariencia:importar-marca')->assertSuccessful();

        $this->assertSame('neutro', $this->ajustes()->theme);
        $this->assertSame('never', $this->ajustes()->logo_plate);
    }

    public function test_sin_marca_antigua_no_hace_nada(): void
    {
        $antes = $this->ajustes()->toArray();

        $this->artisan('apariencia:importar-marca')
            ->expectsOutputToContain('nada que importar')
            ->assertSuccessful();

        $this->assertSame($antes, $this->ajustes()->toArray());
    }
}
