<?php

namespace App\Filament\Resources\Licencias\Schemas;

use App\Models\Licencia;
use App\Services\Licencias\GestionarCupoLicencia;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use InvalidArgumentException;

class LicenciaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la licencia')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('nombre_licencia')
                                    ->label('Nombre de la licencia')
                                    ->required()
                                    ->maxLength(150)
                                    ->unique(ignoreRecord: true),
                                Select::make('categoria')
                                    ->label('Categoria')
                                    ->required()
                                    ->default('MICROSOFT')
                                    ->options([
                                        'MICROSOFT' => 'MICROSOFT',
                                        'ADOBE' => 'ADOBE',
                                        'ANTIVIRUS' => 'ANTIVIRUS',
                                        'VPN' => 'VPN',
                                        'OTRO' => 'OTRO',
                                    ]),
                                TextInput::make('total_licencias')
                                    ->label('Total de licencias')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(0)
                                    ->required()
                                    ->default(0)
                                    ->helperText(function (?Licencia $record): string {
                                        if ($record === null) {
                                            return 'Cantidad de asientos adquiridos. Las asignadas no pueden superar este número.';
                                        }

                                        return "Asignadas: {$record->asignadas}. No puede ser menor que las asignadas activas.";
                                    })
                                    ->rule(function (?Licencia $record): Closure {
                                        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                            if ($record === null) {
                                                return;
                                            }

                                            try {
                                                app(GestionarCupoLicencia::class)->assertPuedeReducirTotal(
                                                    $record,
                                                    (int) $value,
                                                );
                                            } catch (InvalidArgumentException $exception) {
                                                $fail($exception->getMessage());
                                            }
                                        };
                                    }),
                                TextInput::make('asignadas_actuales')
                                    ->label('Asignadas')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->hidden(fn (?Licencia $record): bool => $record === null)
                                    ->formatStateUsing(fn (?Licencia $record): ?string => $record === null
                                        ? null
                                        : (string) $record->asignadas),
                                Toggle::make('estado')
                                    ->label('Activa')
                                    ->default(true)
                                    ->required(),
                            ]),
                        Textarea::make('descripcion')
                            ->label('Descripcion')
                            ->rows(4)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
