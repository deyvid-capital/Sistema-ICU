<?php

namespace App\Filament\Resources\RecepcionCelulars\Tables;

use App\Enums\CelularFormularioPdfTipo;
use App\Filament\Resources\Concerns\CelularFormularioPdfActions;
use App\Filament\Resources\Concerns\FirmaEnOrigenActions;
use App\Filament\Resources\RecepcionCelulars\RecepcionCelularResource;
use App\Models\RecepcionCelular;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RecepcionCelularsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (RecepcionCelular $record): string => $record->trashed()
                ? RecepcionCelularResource::getUrl('view', ['record' => $record])
                : RecepcionCelularResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('asignacion.celular.id')
                    ->label('Celular')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedDevicePhoneMobile)
                    ->placeholder('—'),
                TextColumn::make('asignacion.usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                FirmaEnOrigenActions::columnaEstado(CelularFormularioPdfActions::resolverPdf()),
            ])
            ->filters([
                FirmaEnOrigenActions::filtroEstado('pdfs'),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ...CelularFormularioPdfActions::make(CelularFormularioPdfTipo::Recepcion),
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (RecepcionCelular $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (RecepcionCelular $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Formulario de recepción eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (RecepcionCelular $record): bool => $record->trashed())
                        ->successNotificationTitle('Formulario de recepción eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (RecepcionCelular $record): bool => $record->trashed())
                        ->successNotificationTitle('Formulario de recepción restaurado correctamente'),
                ])
                    ->label('Acciones')
                    ->icon(Heroicon::OutlinedEllipsisVertical)
                    ->color('info')
                    ->button()
                    ->tooltip('Acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->successNotificationTitle('Formularios de recepción eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Formularios de recepción eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Formularios de recepción restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
