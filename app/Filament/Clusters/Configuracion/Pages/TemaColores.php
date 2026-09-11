<?php

namespace App\Filament\Clusters\Configuracion\Pages;

use App\Enums\SeccionConfiguracion;
use App\Filament\Clusters\Configuracion\ConfiguracionCluster;
use App\Models\AppearanceSetting;
use App\Services\Configuracion\TemaService;
use App\Support\Auditoria\AuditorConfiguracion;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Tema y colores del panel: sidebar, barra superior, color primario y gris.
 */
class TemaColores extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.clusters.configuracion.pages.tema-colores';

    protected static ?string $cluster = ConfiguracionCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $navigationLabel = 'Tema y colores';

    protected static ?string $title = 'Tema y colores';

    protected static ?string $slug = 'tema-colores';

    protected static ?int $navigationSort = 1;

    /**
     * @var array<string, string|null>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $resolucion = app(TemaService::class)->resolver();

        $this->form->fill([
            'tema' => $resolucion['tema'],
            'color_primario' => $resolucion['primario'],
            'color_gris' => $resolucion['gris'],
            'color_sidebar_claro' => $resolucion['sidebar']['claro'],
            'color_sidebar_oscuro' => $resolucion['sidebar']['oscuro'],
            'color_topbar_claro' => $resolucion['topbar']['claro'],
            'color_topbar_oscuro' => $resolucion['topbar']['oscuro'],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tema')
                    ->description('Los temas usan la paleta corporativa de Capital SAFI. Elige «Personalizado» para ajustar cada color.')
                    ->icon(Heroicon::OutlinedSwatch)
                    ->schema([
                        Radio::make('tema')
                            ->hiddenLabel()
                            ->options($this->opcionesDeTema())
                            ->descriptions($this->descripcionesDeTema())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $tema = config("tema.temas.{$state}");

                                if (! is_array($tema)) {
                                    return;
                                }

                                $set('color_primario', $tema['primario']);
                                $set('color_gris', $tema['gris']);
                                $set('color_sidebar_claro', $tema['sidebar']['claro']);
                                $set('color_sidebar_oscuro', $tema['sidebar']['oscuro']);
                                $set('color_topbar_claro', $tema['topbar']['claro']);
                                $set('color_topbar_oscuro', $tema['topbar']['oscuro']);
                            }),
                    ]),

                Section::make('Vista previa')
                    ->description('Simulación del panel con los colores seleccionados, antes de guardar.')
                    ->icon(Heroicon::OutlinedEye)
                    ->schema([
                        View::make('filament.configuracion.vista-previa')
                            ->columnSpanFull(),
                    ]),

                Section::make('Colores personalizados')
                    ->description('Se recomienda usar los hexadecimales de la paleta corporativa.')
                    ->icon(Heroicon::OutlinedPaintBrush)
                    ->visible(fn (Get $get): bool => $get('tema') === TemaService::TEMA_PERSONALIZADO)
                    ->schema([
                        Grid::make(2)->schema([
                            ColorPicker::make('color_sidebar_claro')
                                ->label('Sidebar — modo claro')
                                ->hex()
                                ->required()
                                ->live(onBlur: true)
                                ->rule($this->reglaDeColor()),
                            ColorPicker::make('color_topbar_claro')
                                ->label('Barra superior — modo claro')
                                ->hex()
                                ->required()
                                ->live(onBlur: true)
                                ->rule($this->reglaDeColor()),
                            ColorPicker::make('color_sidebar_oscuro')
                                ->label('Sidebar — modo oscuro')
                                ->hex()
                                ->required()
                                ->live(onBlur: true)
                                ->rule($this->reglaDeColor()),
                            ColorPicker::make('color_topbar_oscuro')
                                ->label('Barra superior — modo oscuro')
                                ->hex()
                                ->required()
                                ->live(onBlur: true)
                                ->rule($this->reglaDeColor()),
                            ColorPicker::make('color_primario')
                                ->label('Color primario')
                                ->helperText('Botones, enlaces y el ítem activo del menú.')
                                ->hex()
                                ->required()
                                ->live(onBlur: true)
                                ->rule($this->reglaDeColor()),
                            Select::make('color_gris')
                                ->label('Paleta gris')
                                ->helperText('Afecta a fondos, bordes y textos de toda la interfaz, no solo a las barras.')
                                ->options(config('tema.grises_disponibles'))
                                ->required()
                                ->native(false)
                                ->live(),
                        ]),
                        View::make('filament.configuracion.muestras-marca')
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Tokens de color calculados a partir del estado actual del formulario, para
     * la vista previa en vivo.
     *
     * @return array<string, array<string, string>>
     */
    public function vistaPrevia(): array
    {
        $servicio = app(TemaService::class);
        $primario = $this->data['color_primario'] ?? null;

        return [
            'claro' => [
                'sidebar' => $servicio->tokensDeBarra((string) ($this->data['color_sidebar_claro'] ?? '#FFFFFF'), $primario),
                'topbar' => $servicio->tokensDeBarra((string) ($this->data['color_topbar_claro'] ?? '#FFFFFF'), $primario),
            ],
            'oscuro' => [
                'sidebar' => $servicio->tokensDeBarra((string) ($this->data['color_sidebar_oscuro'] ?? '#18181B'), $primario),
                'topbar' => $servicio->tokensDeBarra((string) ($this->data['color_topbar_oscuro'] ?? '#18181B'), $primario),
            ],
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar cambios')
                ->icon(Heroicon::OutlinedCheck)
                ->action('guardar'),
            Action::make('restaurar')
                ->label('Restaurar tema por defecto')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Restaurar tema por defecto')
                ->modalDescription('Se descartarán los colores personalizados. El logo y el favicon no se modifican.')
                ->action('restaurar'),
        ];
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();

        $configuracion = AppearanceSetting::query()->first() ?? new AppearanceSetting;
        $original = $configuracion->getOriginal();
        $configuracion->fill($datos)->save();

        app(AuditorConfiguracion::class)->guardado(
            SeccionConfiguracion::TemaColores,
            $configuracion,
            $original,
        );

        app(TemaService::class)->olvidar();

        Notification::make()
            ->success()
            ->title('Tema actualizado')
            ->body('Los nuevos colores ya están aplicados en el panel.')
            ->send();

        $this->redirect(static::getUrl());
    }

    public function restaurar(): void
    {
        $configuracion = AppearanceSetting::query()->first();
        $original = $configuracion?->getOriginal() ?? [];

        $configuracion?->update([
            'tema' => config('tema.tema_por_defecto'),
            'color_primario' => null,
            'color_gris' => null,
            'color_sidebar_claro' => null,
            'color_sidebar_oscuro' => null,
            'color_topbar_claro' => null,
            'color_topbar_oscuro' => null,
        ]);

        if ($configuracion !== null) {
            app(AuditorConfiguracion::class)->restaurado(
                SeccionConfiguracion::TemaColores,
                $configuracion,
                $original,
            );
        }

        app(TemaService::class)->olvidar();

        Notification::make()
            ->success()
            ->title('Tema restaurado')
            ->send();

        $this->redirect(static::getUrl());
    }

    /**
     * @return array<string, string>
     */
    protected function opcionesDeTema(): array
    {
        $opciones = [];

        foreach (config('tema.temas', []) as $clave => $tema) {
            $opciones[$clave] = $tema['etiqueta'];
        }

        $opciones[TemaService::TEMA_PERSONALIZADO] = 'Personalizado';

        return $opciones;
    }

    /**
     * @return array<string, string>
     */
    protected function descripcionesDeTema(): array
    {
        $descripciones = [];

        foreach (config('tema.temas', []) as $clave => $tema) {
            $descripciones[$clave] = $tema['descripcion'];
        }

        $descripciones[TemaService::TEMA_PERSONALIZADO] = 'Elige cada color manualmente.';

        return $descripciones;
    }

    protected function reglaDeColor(): string
    {
        return 'regex:/^#[0-9A-Fa-f]{6}$/';
    }
}
