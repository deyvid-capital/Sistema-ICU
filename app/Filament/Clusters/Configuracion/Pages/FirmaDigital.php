<?php

namespace App\Filament\Clusters\Configuracion\Pages;

use App\Enums\SeccionConfiguracion;
use App\Filament\Clusters\Configuracion\ConfiguracionCluster;
use App\Models\FirmaSetting;
use App\Support\Auditoria\AuditorConfiguracion;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Ajustes globales del módulo de firma digital (una sola fila en firma_settings).
 */
class FirmaDigital extends Page implements HasForms
{
    use HasPageShield;
    use InteractsWithForms;

    protected string $view = 'filament.clusters.configuracion.pages.firma-digital';

    protected static ?string $cluster = ConfiguracionCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Ajustes de firma';

    protected static ?string $title = 'Ajustes de firma digital';

    protected static ?string $slug = 'firma-digital';

    protected static ?int $navigationSort = 6;

    /**
     * @var array<string, mixed>
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(FirmaSetting::actual()->only([
            'requiere_2fa_al_firmar',
            'intentos_maximos',
            'minutos_bloqueo',
            'plazo_dias_por_defecto',
            'recordatorio_horas_por_defecto',
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Seguridad al firmar')
                    ->description('Cada firma pide la contraseña del sistema del titular.')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->schema([
                        Toggle::make('requiere_2fa_al_firmar')
                            ->label('Pedir también el código de verificación en dos pasos')
                            ->helperText('Desactivado por defecto.'),
                        Grid::make(2)->schema([
                            TextInput::make('intentos_maximos')
                                ->label('Contraseñas incorrectas antes de bloquear')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(20)
                                ->required(),
                            TextInput::make('minutos_bloqueo')
                                ->label('Minutos de bloqueo')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(1440)
                                ->required(),
                        ]),
                    ]),
                Section::make('Valores por defecto de cada envío')
                    ->description('Se pueden cambiar al preparar cada documento.')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('plazo_dias_por_defecto')
                                ->label('Plazo para firmar (días)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(365)
                                ->required(),
                            TextInput::make('recordatorio_horas_por_defecto')
                                ->label('Recordatorio cada (horas)')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(720)
                                ->required(),
                        ]),
                    ]),
            ])
            ->statePath('data');
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
        ];
    }

    public function guardar(): void
    {
        $datos = $this->form->getState();

        $configuracion = FirmaSetting::query()->first() ?? new FirmaSetting;
        $original = $configuracion->getOriginal();

        $configuracion->fill($datos)->save();

        app(AuditorConfiguracion::class)->guardado(SeccionConfiguracion::FirmaDigital, $configuracion, $original);

        Notification::make()
            ->success()
            ->title('Ajustes de firma digital actualizados')
            ->send();
    }
}
