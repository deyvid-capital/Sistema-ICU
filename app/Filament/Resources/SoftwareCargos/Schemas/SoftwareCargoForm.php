<?php

namespace App\Filament\Resources\SoftwareCargos\Schemas;

use App\Models\Software;
use App\Models\SoftwareCargo;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class SoftwareCargoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de software al cargo')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('cargo_id')
                            ->label('Cargo')
                            ->relationship('cargo', 'cargo')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('software_ids', [])),
                        Select::make('software_ids')
                            ->label('Software')
                            ->helperText('Puedes elegir varios. Todos quedan asignados al cargo seleccionado.')
                            ->options(fn (Get $get): array => self::softwareDisponibleParaCargo($get('cargo_id')))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visibleOn(Operation::Create)
                            ->disabled(fn (Get $get): bool => blank($get('cargo_id')))
                            ->columnSpanFull()
                            ->rule(fn (Get $get): Closure => self::softwareIdsSonAsignables($get)),
                        Select::make('software_id')
                            ->label('Software')
                            ->relationship('software', 'nombre_software')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->hiddenOn(Operation::Create)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: function (Unique $rule, Get $get, ?SoftwareCargo $record): Unique {
                                    return $rule
                                        ->where('cargo_id', $get('cargo_id'))
                                        ->ignore($record);
                                },
                            ),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function softwareDisponibleParaCargo(mixed $cargoId): array
    {
        if (blank($cargoId)) {
            return [];
        }

        return Software::query()
            ->whereDoesntHave(
                'cargos',
                fn (Builder $cargos): Builder => $cargos->where('cargo_id', $cargoId),
            )
            ->orderBy('nombre_software')
            ->pluck('nombre_software', 'id_software')
            ->all();
    }

    private static function softwareIdsSonAsignables(Get $get): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
            if (! is_array($value) || $value === []) {
                return;
            }

            $cargoId = $get('cargo_id');

            if (blank($cargoId)) {
                $fail('Elige un cargo antes de seleccionar software.');

                return;
            }

            $ids = collect($value)
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values();

            $existentes = Software::query()
                ->whereIn('id_software', $ids)
                ->count();

            if ($existentes !== $ids->count()) {
                $fail('Hay software seleccionados que no existen.');

                return;
            }

            $yaAsignados = SoftwareCargo::query()
                ->where('cargo_id', $cargoId)
                ->whereIn('software_id', $ids)
                ->exists();

            if ($yaAsignados) {
                $fail('Algunos software ya estan asignados a este cargo.');
            }
        };
    }
}
