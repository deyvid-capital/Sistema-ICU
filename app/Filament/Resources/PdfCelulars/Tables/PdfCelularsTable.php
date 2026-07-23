<?php

namespace App\Filament\Resources\PdfCelulars\Tables;

use App\Filament\Resources\EntregaCelulars\RelationManagers\PdfsRelationManager as EntregaCelularPdfsRelationManager;
use App\Filament\Resources\RecepcionCelulars\RelationManagers\PdfsRelationManager as RecepcionCelularPdfsRelationManager;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PdfCelularsTable
{
    /**
     * @var list<class-string>
     */
    private const OWNER_RELATION_MANAGERS = [
        EntregaCelularPdfsRelationManager::class,
        RecepcionCelularPdfsRelationManager::class,
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fecha', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->weight('medium'),
                TextColumn::make('documento_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => class_basename((string) $state))
                    ->searchable()
                    ->hiddenOn(self::OWNER_RELATION_MANAGERS),
                TextColumn::make('documento_id')
                    ->label('Documento')
                    ->numeric()
                    ->sortable()
                    ->hiddenOn(self::OWNER_RELATION_MANAGERS),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('url')
                    ->label('Archivo')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? basename($state) : '—')
                    ->url(fn (?string $state): ?string => filled($state) ? Storage::disk('public')->url($state) : null)
                    ->openUrlInNewTab()
                    ->placeholder('—'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
                    EditAction::make()->label('Editar'),
                    DeleteAction::make()
                        ->label('Eliminar')
                        ->successNotificationTitle('PDF eliminado correctamente'),
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
                        ->successNotificationTitle('PDFs eliminados correctamente'),
                ])
                    ->label('Acciones masivas'),
            ]);
    }
}
