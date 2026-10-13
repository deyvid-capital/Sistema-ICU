<?php

namespace App\Filament\Resources\Equipos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class EquipoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identificación del equipo')
                    ->description('Datos principales y estado del equipo.')
                    ->icon(Heroicon::OutlinedComputerDesktop)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('service_tag')
                            ->label('Service Tag')
                            ->icon(Heroicon::OutlinedHashtag)
                            ->weight(FontWeight::Bold)
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('nombre_equipo')
                            ->label('Nombre del equipo')
                            ->placeholder('—'),
                        TextEntry::make('codigo_activo')
                            ->label('Código de activo')
                            ->placeholder('—'),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->placeholder('—'),
                        TextEntry::make('observaciones')
                            ->label('Observaciones')
                            ->html()
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull(),
                    ]),
                Section::make('Hardware y software')
                    ->icon(Heroicon::OutlinedCpuChip)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('modelo.nombre')
                            ->label('Modelo')
                            ->placeholder('—'),
                        TextEntry::make('microprocesador.nombre_micro')
                            ->label('Microprocesador')
                            ->placeholder('—'),
                        TextEntry::make('ram.tipo_ram')
                            ->label('Memoria RAM')
                            ->placeholder('—'),
                        TextEntry::make('gpu.nombre_gpu')
                            ->label('Tarjeta de video')
                            ->placeholder('—'),
                        TextEntry::make('disco_duro')
                            ->label('Disco duro')
                            ->placeholder('—'),
                        TextEntry::make('proveedor')
                            ->label('Proveedor')
                            ->placeholder('—'),
                        TextEntry::make('so')
                            ->label('Sistema operativo')
                            ->placeholder('—'),
                        TextEntry::make('licencia_so')
                            ->label('Licencia SO')
                            ->placeholder('—'),
                    ]),
                Section::make('Garantía y seguridad')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('inicio_garantia')
                            ->label('Inicio de garantía')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('fin_garantia')
                            ->label('Vencimiento de garantía')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        IconEntry::make('bloqueo_bios')
                            ->label('Bloqueo BIOS')
                            ->boolean(),
                        IconEntry::make('etiquetado')
                            ->label('Etiquetado')
                            ->boolean(),
                        IconEntry::make('usuario_admin')
                            ->label('Usuario administrador')
                            ->boolean(),
                        IconEntry::make('litigation_hold')
                            ->label('Litigation Hold')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckCircle)
                            ->falseIcon(Heroicon::OutlinedXCircle)
                            ->trueColor('success')
                            ->falseColor('danger'),
                        IconEntry::make('bitlocker')
                            ->label('BitLocker')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedCheckCircle)
                            ->falseIcon(Heroicon::OutlinedXCircle)
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
