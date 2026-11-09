<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Equipos\EquipoResource;
use App\Models\Equipo;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Equipos cuya garantía vence en los próximos 90 días.
 */
class GarantiasPorVencerTable extends TableWidget
{
    use HasWidgetShield;

    /**
     * Días hacia adelante que se consideran "por vencer".
     */
    public const DIAS_DE_AVISO = 90;

    protected static ?int $sort = 6;

    protected static ?string $heading = 'Garantías por vencer';

    public function table(Table $table): Table
    {
        return $table
            ->description('Equipos cuya garantía vence en los próximos '.self::DIAS_DE_AVISO.' días.')
            ->query(fn (): Builder => Equipo::query()
                ->with('modelo')
                ->whereBetween('venc_garantia', [today(), today()->addDays(self::DIAS_DE_AVISO)]))
            ->defaultSort('venc_garantia')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Ninguna garantía vence pronto')
            ->emptyStateIcon(Heroicon::OutlinedShieldCheck)
            ->columns([
                TextColumn::make('service_tag')
                    ->label('Service tag')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('nombre_equipo')
                    ->label('Equipo')
                    ->description(fn (Equipo $record): ?string => $record->modelo?->nombre)
                    ->searchable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('venc_garantia')
                    ->label('Vence')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('dias_restantes')
                    ->label('Días restantes')
                    ->state(fn (Equipo $record): int => (int) today()->diffInDays($record->venc_garantia, absolute: false))
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 30 => 'danger',
                        $state <= 60 => 'warning',
                        default => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon(Heroicon::OutlinedEye)
                    ->url(fn (Equipo $record): string => EquipoResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (Equipo $record): bool => EquipoResource::canView($record)),
            ]);
    }
}
