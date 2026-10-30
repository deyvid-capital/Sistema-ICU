<?php

namespace App\Filament\Resources\EntregaCelulars\Tables;

use App\Enums\CelularFormularioPdfTipo;
use App\Filament\Resources\Concerns\CelularFormularioPdfActions;
use App\Filament\Resources\Concerns\FirmaEnOrigenActions;
use App\Filament\Resources\EntregaCelulars\EntregaCelularResource;
use App\Models\EntregaCelular;
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

class EntregaCelularsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (EntregaCelular $record): string => $record->trashed()
                ? EntregaCelularResource::getUrl('view', ['record' => $record])
                : EntregaCelularResource::getUrl('edit', ['record' => $record]))
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
                    ...CelularFormularioPdfActions::make(CelularFormularioPdfTipo::Entrega),
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (EntregaCelular $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (EntregaCelular $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Formulario de entrega eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (EntregaCelular $record): bool => $record->trashed())
                        ->successNotificationTitle('Formulario de entrega eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (EntregaCelular $record): bool => $record->trashed())
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
