<?php

namespace App\Filament\Imports;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nombres')
                ->label('Nombres')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Juan'),
            ImportColumn::make('apellidos')
                ->label('Apellidos')
                ->requiredMapping()
                ->rules(['required', 'max:255'])
                ->example('Pérez'),
            ImportColumn::make('email')
                ->label('Correo')
                ->requiredMapping()
                ->rules(['required', 'email', 'max:100'])
                ->example('jperez@empresa.com'),
            ImportColumn::make('carnet')
                ->label('Carnet')
                ->rules(['nullable', 'max:50'])
                ->example('1234 LP'),
            ImportColumn::make('telefono')
                ->label('Teléfono')
                ->rules(['nullable', 'max:30'])
                ->example('7123-4567'),
            ImportColumn::make('area')
                ->label('Área')
                ->relationship(resolveUsing: 'area')
                ->requiredMapping()
                ->rules(['required'])
                ->example('Tecnología'),
            ImportColumn::make('cargo')
                ->label('Cargo')
                ->relationship(resolveUsing: function (string $state, array $data): ?Cargo {
                    $area = Area::query()
                        ->where('area', $data['area'] ?? null)
                        ->first();

                    if (! $area) {
                        return null;
                    }

                    return Cargo::query()
                        ->where('area_id', $area->id_area)
                        ->where('cargo', $state)
                        ->first();
                })
                ->requiredMapping()
                ->rules(['required'])
                ->example('Analista'),
            ImportColumn::make('password')
                ->label('Contraseña')
                ->sensitive()
                ->ignoreBlankState()
                ->rules(['nullable', 'min:8'])
                ->example('password123'),
        ];
    }

    public function resolveRecord(): User
    {
        return User::query()
            ->where('email', $this->data['email'] ?? null)
            ->first() ?? new User;
    }

    protected function afterFill(): void
    {
        $this->record->name = trim(implode(' ', array_filter(
            [$this->record->nombres, $this->record->apellidos],
            fn (?string $value): bool => filled($value),
        )));
    }

    protected function beforeCreate(): void
    {
        if (filled($this->record->getAttributes()['password'] ?? null)) {
            return;
        }

        $passwordColumn = collect(Schema::getColumns((new User)->getTable()))
            ->firstWhere('name', 'password');

        if ($passwordColumn && ($passwordColumn['nullable'] ?? false)) {
            $this->record->password = null;

            return;
        }

        $this->record->password = Str::password();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'La importación de usuarios finalizó: '.Number::format($import->successful_rows).' '.str('fila')->plural($import->successful_rows).' importadas.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('fila')->plural($failedRowsCount).' fallaron.';
        }

        return $body;
    }
}
