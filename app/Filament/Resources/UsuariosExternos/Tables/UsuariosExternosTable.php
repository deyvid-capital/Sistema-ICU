<?php

namespace App\Filament\Resources\UsuariosExternos\Tables;

use App\Enums\PermisoDocumentoCompartido;
use App\Filament\Resources\Concerns\FirmaEnOrigenActions;
use App\Filament\Resources\Concerns\UsuarioExternoFormularioPdfActions;
use App\Filament\Resources\UsuariosExternos\UsuarioExternoResource;
use App\Models\UsuarioExterno;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsuariosExternosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn (UsuarioExterno $record): string => $record->trashed()
                ? UsuarioExternoResource::getUrl('view', ['record' => $record])
                : UsuarioExternoResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('fecha_inicio', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('numero')
                    ->label('N°')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->fontFamily('mono'),
                TextColumn::make('nombre_contacto')
                    ->label('Contacto')
                    ->description(fn (UsuarioExterno $record): string => $record->correo)
                    ->searchable(['nombre_contacto', 'correo'])
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('empresa')
                    ->label('Empresa')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sitioSharepoint.nombre_sitio')
                    ->label('SharePoint')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('onedrive')
                    ->label('OneDrive')
                    ->placeholder('—')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('permiso')
                    ->label('Permiso')
                    ->badge(),
                TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_fin')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('recordatorio')
                    ->label('Aviso de fecha fin')
                    ->state(fn (UsuarioExterno $record): string => $record->estadoRecordatorio())
                    ->toggleable(isToggledHiddenByDefault: true),
                FirmaEnOrigenActions::columnaEstado(UsuarioExternoFormularioPdfActions::resolverPdf()),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                FirmaEnOrigenActions::filtroEstado('pdfs'),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ]),
                SelectFilter::make('permiso')
                    ->label('Permiso')
                    ->options(PermisoDocumentoCompartido::class),
                SelectFilter::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name')
                    ->searchable()
                    ->preload(),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ...UsuarioExternoFormularioPdfActions::make(),
                    ViewAction::make()->label('Ver'),
                    EditAction::make()
                        ->label('Editar')
                        ->visible(fn (UsuarioExterno $record): bool => ! $record->trashed()),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->visible(fn (UsuarioExterno $record): bool => ! $record->trashed())
                        ->successNotificationTitle('Usuario externo eliminado correctamente'),
                    ForceDeleteAction::make()
                        ->label('Eliminar permanentemente')
                        ->visible(fn (UsuarioExterno $record): bool => $record->trashed())
                        ->successNotificationTitle('Usuario externo eliminado permanentemente'),
                    RestoreAction::make()
                        ->label('Restaurar')
                        ->visible(fn (UsuarioExterno $record): bool => $record->trashed())
                        ->successNotificationTitle('Usuario externo restaurado correctamente'),
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
                        ->label('Eliminar seleccionadas')
                        ->successNotificationTitle('Usuarios externos eliminados correctamente'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente')
                        ->successNotificationTitle('Usuarios externos eliminados permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionadas')
                        ->successNotificationTitle('Usuarios externos restaurados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
