<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use App\Models\AzureAd;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AzureAdRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'azureAd';

    protected static ?string $title = 'Azure AD';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('iniciales')
                    ->label('Iniciales')
                    ->maxLength(10),
                TextInput::make('sincronizacion')
                    ->label('Sincronizacion')
                    ->maxLength(50),
                Toggle::make('mfa')
                    ->label('MFA'),
                Toggle::make('estado')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('iniciales')
                    ->label('Iniciales')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('sincronizacion')
                    ->label('Sincronizacion')
                    ->placeholder('—'),
                IconColumn::make('mfa')
                    ->label('MFA')
                    ->boolean(),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Inactivo',
                    ]),
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar cuenta')
                    ->successNotificationTitle('Cuenta registrada correctamente'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (AzureAd $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AzureAd $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Cuenta eliminada correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AzureAd $record): bool => $record->trashed())
                    ->successNotificationTitle('Cuenta eliminada permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AzureAd $record): bool => $record->trashed())
                    ->successNotificationTitle('Cuenta restaurada correctamente'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                    ForceDeleteBulkAction::make()
                        ->label('Eliminar permanentemente'),
                    RestoreBulkAction::make()
                        ->label('Restaurar seleccionados'),
                ]),
            ]);
    }
}
