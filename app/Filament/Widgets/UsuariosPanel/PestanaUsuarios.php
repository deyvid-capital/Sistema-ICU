<?php

namespace App\Filament\Widgets\UsuariosPanel;

use App\Filament\Widgets\UsuariosPanelWidget;
use App\Models\User;
use App\Support\MatrizPerfiles\ExportadorMatrizPerfilesXlsx;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Base de cada pestaña del panel de usuarios del dashboard.
 *
 * Aporta lo común a todas: la consulta de usuarios (incluye a todos, sin
 * importar si están de alta o de baja), la columna Usuario con el correo
 * debajo, Área y cargo, los filtros de área, cargo y movimiento, búsqueda y
 * paginación. Cada pestaña solo declara sus columnas, badges y filtros propios.
 *
 * No se descubre como widget suelto: solo se muestra dentro de UsuariosPanelWidget
 * y hereda su permiso.
 */
abstract class PestanaUsuarios extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    abstract public static function getEtiqueta(): string;

    abstract public static function getIcono(): Heroicon;

    public static function canView(): bool
    {
        return UsuariosPanelWidget::canView();
    }

    /**
     * Columnas de badges de asignaciones activas.
     *
     * @return list<ColumnaDeBadges>
     */
    protected function columnasDeBadges(): array
    {
        return [];
    }

    /**
     * Relaciones extra a cargar de antemano para las columnas propias.
     *
     * @return array<int|string, mixed>
     */
    protected function relaciones(): array
    {
        return [];
    }

    /**
     * @return list<Column>
     */
    protected function columnasPropias(): array
    {
        return [];
    }

    /**
     * @return list<BaseFilter>
     */
    protected function filtrosPropios(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        $badges = $this->columnasDeBadges();

        return $table
            ->heading(static::getEtiqueta())
            ->query(fn (): Builder => User::query()->with([
                'area',
                'cargo',
                ...$this->relaciones(),
                ...collect($badges)->flatMap(fn (ColumnaDeBadges $badge): array => $badge->carga())->all(),
            ]))
            ->columns([
                $this->columnaUsuario(),
                $this->columnaAreaCargo(),
                ...$this->columnasPropias(),
                ...array_map(fn (ColumnaDeBadges $badge): TextColumn => $badge->columna(), $badges),
            ])
            ->filters([
                SelectFilter::make('id_area')
                    ->label('Área')
                    ->relationship('area', 'area')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                SelectFilter::make('id_cargo')
                    ->label('Cargo')
                    ->relationship('cargo', 'cargo')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                $this->filtroDeMovimiento(),
                ...$this->filtrosPropios(),
            ])
            ->headerActions([
                $this->matrizDePerfilesAction(),
            ])
            ->filtersFormColumns(2)
            ->defaultSort('name')
            ->paginated([10, 25, 50])
            ->paginationMode(PaginationMode::Default)
            ->striped()
            ->emptyStateHeading('Sin usuarios que coincidan')
            ->emptyStateIcon(Heroicon::OutlinedUsers);
    }

    /**
     * Excel con una fila por cargo y sus accesos y software (ver MatrizPerfiles).
     * No depende de los filtros de la tabla: siempre resume todos los cargos.
     */
    protected function matrizDePerfilesAction(): Action
    {
        return Action::make('matrizDePerfiles')
            ->label('Matriz de perfiles')
            ->icon(Heroicon::OutlinedTableCells)
            ->color('gray')
            ->action(fn (): StreamedResponse => app(ExportadorMatrizPerfilesXlsx::class)->descargar());
    }

    protected function columnaUsuario(): TextColumn
    {
        return TextColumn::make('name')
            ->label('Usuario')
            ->description(fn (User $record): ?string => $record->email)
            ->weight('medium')
            ->searchable(['name', 'nombres', 'apellidos', 'email', 'carnet'])
            ->sortable();
    }

    protected function columnaAreaCargo(): TextColumn
    {
        return TextColumn::make('area.area')
            ->label('Área y cargo')
            ->description(fn (User $record): ?string => $record->cargo?->cargo)
            ->placeholder('Sin área')
            ->searchable()
            ->visibleFrom('md')
            ->toggleable();
    }

    /**
     * Estado según el último movimiento del usuario (User::estaDeAlta()).
     */
    protected function filtroDeMovimiento(): SelectFilter
    {
        return SelectFilter::make('movimiento')
            ->label('Movimiento')
            ->options([
                'Alta' => 'De alta',
                'Baja' => 'De baja',
                'ninguno' => 'Sin movimiento',
            ])
            ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                'Alta', 'Baja' => $query->whereHas('ultimoMovimiento', fn (Builder $query): Builder => $query->where('tipo', $data['value'])),
                'ninguno' => $query->whereDoesntHave('movimientos'),
                default => $query,
            });
    }

    /**
     * Filtro "tiene alguno de estos tipos" construido a partir de las columnas de badges.
     *
     * @param  list<ColumnaDeBadges>  $badges
     */
    protected function filtroPorTipoDeBadge(string $nombre, string $etiqueta, array $badges): SelectFilter
    {
        $porNombre = collect($badges)->keyBy('nombre');

        return SelectFilter::make($nombre)
            ->label($etiqueta)
            ->multiple()
            ->options($porNombre->map(fn (ColumnaDeBadges $badge): string => $badge->etiqueta)->all())
            ->query(function (Builder $query, array $data) use ($porNombre): Builder {
                $elegidas = $porNombre->only($data['values'] ?? []);

                if ($elegidas->isEmpty()) {
                    return $query;
                }

                return $query->where(function (Builder $query) use ($elegidas): void {
                    $elegidas->each(fn (ColumnaDeBadges $badge) => $badge->conAlguna($query, 'or'));
                });
            });
    }
}
