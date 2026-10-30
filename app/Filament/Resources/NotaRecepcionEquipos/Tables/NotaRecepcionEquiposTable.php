<?php

namespace App\Filament\Resources\NotaRecepcionEquipos\Tables;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use App\Filament\Resources\Concerns\FirmaEnOrigenActions;
use App\Filament\Resources\Equipos\RelationManagers\NotasRecepcionRelationManager as EquipoNotasRecepcionRelationManager;
use App\Filament\Resources\NotaRecepcionEquipos\NotaRecepcionEquipoResource;
use App\Filament\Resources\UsuarioExternos\RelationManagers\NotasRecepcionRelationManager as UsuarioExternoNotasRecepcionRelationManager;
use App\Models\NotaRecepcionEquipo;
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

class NotaRecepcionEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (NotaRecepcionEquipo $record): string => $record->trashed()
                ? NotaRecepcionEquipoResource::getUrl('view', ['record' => $record])
                : NotaRecepcionEquipoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('equipo.service_tag')
                    ->label('Equipo')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->placeholder('—')
                    ->hiddenOn(EquipoNotasRecepcionRelationManager::class),
                TextColumn::make('usuarioExterno.nombre')
                    ->label('Usuario externo')
                    ->searchable()
                    ->placeholder('—')
                    ->hiddenOn(UsuarioExternoNotasRecepcionRelationManager::class),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable(),
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
                FirmaEnOrigenActions::columnaEstado(EquipoFormularioPdfActions::resolverPdf()),
            ])
            ->filters([
                FirmaEnOrigenActions::filtroEstado('pdfs'),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ...EquipoFormularioPdfActions::make(EquipoFormularioPdfTipo::NotaRecepcion),
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (NotaRecepcionEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (NotaRecepcionEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Nota de recepción eliminada correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (NotaRecepcionEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Nota de recepción eliminada permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (NotaRecepcionEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Nota de recepción restaurada correctamente'),
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
                        ->successNotificationTitle('Notas de recepción eliminadas correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Notas de recepción eliminadas permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Notas de recepción restauradas correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
