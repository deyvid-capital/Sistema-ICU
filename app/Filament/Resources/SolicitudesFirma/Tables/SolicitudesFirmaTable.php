<?php

namespace App\Filament\Resources\SolicitudesFirma\Tables;

use App\Enums\EstadoSolicitudFirma;
use App\Models\SolicitudFirma;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SolicitudesFirmaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Sin envíos')
            ->emptyStateDescription('Usa "Preparar envío" para mandar documentos a firmar.')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('asunto')
                    ->label('Asunto')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('documentos_count')
                    ->label('Documentos')
                    ->badge()
                    ->color('info'),
                TextColumn::make('firmantes_count')
                    ->label('Firmantes')
                    ->badge()
                    ->color('gray')
                    ->icon(fn (SolicitudFirma $record): ?Heroicon => $record->orden_obligatorio ? Heroicon::OutlinedListBullet : null)
                    ->tooltip(fn (SolicitudFirma $record): ?string => $record->orden_obligatorio ? 'Con orden obligatorio' : null),
                TextColumn::make('creador.name')
                    ->label('Enviado por')
                    ->toggleable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('fecha_limite')
                    ->label('Fecha límite')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Sin fecha'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(EstadoSolicitudFirma::class)
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
            ]);
    }
}
