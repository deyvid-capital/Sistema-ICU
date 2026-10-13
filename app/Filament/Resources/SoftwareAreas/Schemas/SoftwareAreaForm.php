<?php

namespace App\Filament\Resources\SoftwareAreas\Schemas;

use App\Models\Software;
use App\Models\SoftwareArea;
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

class SoftwareAreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Asignación de software al área')
                    ->icon(Heroicon::OutlinedBuildingOffice)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('area_id')
                            ->label('Area')
                            ->relationship('area', 'area')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('software_ids', [])),
                        Select::make('software_ids')
                            ->label('Software')
                            ->helperText('Puedes elegir varios. Todos quedan asignados al area seleccionada.')
                            ->options(fn (Get $get): array => self::softwareDisponibleParaArea($get('area_id')))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visibleOn(Operation::Create)
                            ->disabled(fn (Get $get): bool => blank($get('area_id')))
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
                                modifyRuleUsing: function (Unique $rule, Get $get, ?SoftwareArea $record): Unique {
                                    return $rule
                                        ->where('area_id', $get('area_id'))
                                        ->ignore($record);
                                },
                            ),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function softwareDisponibleParaArea(mixed $areaId): array
    {
        if (blank($areaId)) {
            return [];
        }

        return Software::query()
            ->whereDoesntHave(
                'areas',
                fn (Builder $areas): Builder => $areas->where('area_id', $areaId),
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

            $areaId = $get('area_id');

            if (blank($areaId)) {
                $fail('Elige un area antes de seleccionar software.');

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

            $yaAsignados = SoftwareArea::query()
                ->where('area_id', $areaId)
                ->whereIn('software_id', $ids)
                ->exists();

            if ($yaAsignados) {
                $fail('Algunos software ya estan asignados a esta area.');
            }
        };
    }
}
