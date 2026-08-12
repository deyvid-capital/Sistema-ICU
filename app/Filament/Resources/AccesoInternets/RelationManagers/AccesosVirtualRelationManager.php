<?php

namespace App\Filament\Resources\AccesoInternets\RelationManagers;

use App\Models\AccesoVirtual;
use App\Models\RecursoVpn;
use App\Models\Virtual;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Validation\Rules\Unique;

class AccesosVirtualRelationManager extends RelationManager
{
    protected static string $relationship = 'accesosVirtual';

    protected static ?string $title = 'Accesos virtuales (VM + VPN)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('virtual_id')
                    ->label('Máquina virtual')
                    ->options(fn (): array => Virtual::query()
                        ->where('estado', true)
                        ->orderBy('maquina_virtual')
                        ->pluck('maquina_virtual', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Select::make('vpn_id')
                    ->label('Recurso VPN')
                    ->options(fn (): array => RecursoVpn::query()
                        ->where('estado', true)
                        ->orderBy('nombre_vpn')
                        ->pluck('nombre_vpn', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get, ?AccesoVirtual $record): Unique {
                            return $rule
                                ->where('internet_id', $this->ownerRecord->getKey())
                                ->where('virtual_id', $get('virtual_id'))
                                ->ignore($record);
                        },
                    ),
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
            ->columns([
                TextColumn::make('virtual.maquina_virtual')
                    ->label('Máquina virtual')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('recursoVpn.nombre_vpn')
                    ->label('Recurso VPN')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->since()
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
                    ->label('Nuevo acceso virtual')
                    ->successNotificationTitle('Acceso virtual registrado correctamente'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->visible(fn (AccesoVirtual $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn (AccesoVirtual $record): bool => ! $record->trashed())
                    ->successNotificationTitle('Acceso virtual eliminado correctamente'),
                ForceDeleteAction::make()
                    ->label('Eliminar permanentemente')
                    ->visible(fn (AccesoVirtual $record): bool => $record->trashed())
                    ->successNotificationTitle('Acceso virtual eliminado permanentemente'),
                RestoreAction::make()
                    ->label('Restaurar')
                    ->visible(fn (AccesoVirtual $record): bool => $record->trashed())
                    ->successNotificationTitle('Acceso virtual restaurado correctamente'),
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
