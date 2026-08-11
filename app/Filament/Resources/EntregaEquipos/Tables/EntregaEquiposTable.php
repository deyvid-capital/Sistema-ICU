<?php

namespace App\Filament\Resources\EntregaEquipos\Tables;

use App\Enums\EquipoFormularioPdfTipo;
use App\Filament\Resources\Concerns\EquipoFormularioPdfActions;
use App\Filament\Resources\EntregaEquipos\EntregaEquipoResource;
use App\Models\EntregaEquipo;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class EntregaEquiposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (EntregaEquipo $record): string => $record->trashed()
                ? EntregaEquipoResource::getUrl('view', ['record' => $record])
                : EntregaEquipoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('asignacion.service_tag')
                    ->label('Equipo')
                    ->searchable(['service_tag'])
                    ->sortable()
                    ->weight('medium')
                    ->icon(Heroicon::OutlinedComputerDesktop)
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
                IconColumn::make('mouse')
                    ->label('Mouse')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('cooler')
                    ->label('Cooler')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('audifonos')
                    ->label('Audífonos')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ...EquipoFormularioPdfActions::make(EquipoFormularioPdfTipo::Entrega),
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (EntregaEquipo $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (EntregaEquipo $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Formulario de entrega eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (EntregaEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Formulario de entrega eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (EntregaEquipo $record): bool => $record->trashed())
                        ->successNotificationTitle('Formulario de entrega restaurado correctamente'),
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
                        ->successNotificationTitle('Formularios de entrega eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Formularios de entrega eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados')
                        ->successNotificationTitle('Formularios de entrega restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
