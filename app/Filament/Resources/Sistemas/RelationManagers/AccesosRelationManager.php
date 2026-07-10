<?php

namespace App\Filament\Resources\Sistemas\RelationManagers;

use App\Models\AccesoSistema;
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
use Filament\Forms\Components\Textarea;
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

class AccesosRelationManager extends RelationManager
{
    protected static string $relationship = 'accesos';

    protected static ?string $title = 'Accesos al sistema';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('usuario_sistema')
                    ->label('Usuario en el sistema')
                    ->required()
                    ->maxLength(50),
                TextInput::make('nivel_acceso')
                    ->label('Nivel de acceso')
                    ->required()
                    ->maxLength(100),
                DatePicker::make('fecha_inicio')
                    ->label('Fecha de inicio')
                    ->required()
                    ->default(now()),
                DatePicker::make('fecha_fin')
                    ->label('Fecha de fin')
                    ->afterOrEqual('fecha_inicio'),
                Toggle::make('estado')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]))
            ->defaultSort('fecha_inicio', 'desc')
            ->columns([
                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('usuario_sistema')
                    ->label('Usuario sistema')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nivel_acceso')
                    ->label('Nivel')
                    ->badge()
                    ->color('info'),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('fecha_fin')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->placeholder('—'),
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
                    ->label('Nuevo acceso')
                    ->successNotificationTitle('Acceso al sistema registrado correctamente'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (AccesoSistema $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccesoSistema $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Acceso eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccesoSistema $record): bool => $record->trashed())
                    ->successNotificationTitle('Acceso eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccesoSistema $record): bool => $record->trashed())
                    ->successNotificationTitle('Acceso restaurado correctamente'),
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
