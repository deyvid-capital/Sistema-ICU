<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\AccesoVirtuals\AccesoVirtualResource;
use App\Filament\Resources\Users\RelationManagers\Concerns\HasOwnerRecordCountBadge;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AccesosVirtualRelationManager extends RelationManager
{
    use HasOwnerRecordCountBadge;

    protected static string $relationship = 'accesosVirtual';

    protected static ?string $title = 'Accesos virtuales (VM + VPN)';

    public function form(Schema $schema): Schema
    {
        return AccesoVirtualResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return AccesoVirtualResource::table($table)
            ->headerActions([
                CreateAction::make()
                    ->label('Asignar acceso virtual')
                    ->fillForm(fn (): array => [
                        'usuario_id' => $this->getOwnerRecord()->getKey(),
                        'estado' => true,
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->getOwnerRecord()->getKey();

                        return $data;
                    })
                    ->successNotificationTitle('Acceso virtual registrado correctamente'),
            ]);
    }
}
