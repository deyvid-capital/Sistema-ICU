<?php

namespace App\Filament\Resources\Fondos\RelationManagers;

use App\Models\AccesoDaza;
use App\Models\User;
use App\Rules\NotTrashed;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AccesosDazaRelationManager extends RelationManager
{
    protected static string $relationship = 'accesosDaza';

    protected static ?string $title = 'Accesos DAZA';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rule(new NotTrashed(User::class)),
                TextInput::make('usuario_daza')
                    ->label('Usuario DAZA')
                    ->required()
                    ->maxLength(50),
                TextInput::make('nivel_acceso')
                    ->label('Nivel de acceso')
                    ->required()
                    ->maxLength(50),
                TextInput::make('ambiente')
                    ->label('Ambiente')
                    ->required()
                    ->maxLength(50),
                Toggle::make('estado')
                    ->label('Activo')
                    ->default(true),
                DatePicker::make('fecha')
                    ->label('Fecha')
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort('fecha', 'desc')
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('usuario_daza')
                    ->label('Usuario DAZA')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nivel_acceso')
                    ->label('Nivel')
                    ->badge()
                    ->color('info'),
                TextColumn::make('ambiente')
                    ->label('Ambiente')
                    ->searchable(),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Estado del registro'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo acceso DAZA')
                    ->successNotificationTitle('Acceso DAZA registrado correctamente'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (AccesoDaza $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccesoDaza $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Acceso DAZA eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccesoDaza $record): bool => $record->trashed())
                    ->successNotificationTitle('Acceso DAZA eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccesoDaza $record): bool => $record->trashed())
                    ->successNotificationTitle('Acceso DAZA restaurado correctamente'),
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
