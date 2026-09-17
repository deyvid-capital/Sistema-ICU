<?php

namespace App\Filament\Resources\SoftwareExcs\Schemas;

use App\Enums\EstadoAprobacionExcepcion;
use App\Models\SoftwareExc;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;

class SoftwareExcInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Excepción')
                    ->description('Permiso especial pedido para un colaborador y un software.')
                    ->icon(Heroicon::OutlinedShieldExclamation)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('usuario.name')
                            ->label('Usuario')
                            ->icon(Heroicon::OutlinedUser)
                            ->weight(FontWeight::Medium)
                            ->placeholder('—'),
                        TextEntry::make('software.nombre_software')
                            ->label('Software')
                            ->icon(Heroicon::OutlinedCube)
                            ->badge()
                            ->color('info')
                            ->placeholder('—'),
                        TextEntry::make('tipo_excepcion')
                            ->label('Tipo')
                            ->icon(Heroicon::OutlinedTag)
                            ->badge()
                            ->color(fn (SoftwareExc $record): string => static::colorTipo($record->tipo_excepcion))
                            ->placeholder('—'),
                        TextEntry::make('fecha_inicio')
                            ->label('Vigente desde')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('—'),
                        TextEntry::make('fecha_fin')
                            ->label('Vigente hasta')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->date('d/m/Y')
                            ->placeholder('Sin vencimiento'),
                        TextEntry::make('motivo')
                            ->label('Motivo')
                            ->placeholder('Sin motivo registrado')
                            ->columnSpanFull(),
                    ]),
                Section::make('Aprobación')
                    ->description(fn (SoftwareExc $record): string => match ($record->estado_aprobacion) {
                        EstadoAprobacionExcepcion::Pendiente => 'Pendiente: el aprobador designado debe aceptar o rechazar. Mientras tanto puedes cambiar el área o la persona.',
                        EstadoAprobacionExcepcion::Aprobada => 'Esta excepción ya fue aprobada. El área y el aprobador quedaron fijos.',
                        EstadoAprobacionExcepcion::Rechazada => 'Esta excepción fue rechazada. El área y el aprobador quedaron fijos.',
                        default => 'Estado de la autorización.',
                    })
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('estado_aprobacion')
                            ->label('Estado')
                            ->badge()
                            ->size(TextSize::Large)
                            ->icon(fn (SoftwareExc $record): Heroicon => match ($record->estado_aprobacion) {
                                EstadoAprobacionExcepcion::Pendiente => Heroicon::OutlinedClock,
                                EstadoAprobacionExcepcion::Aprobada => Heroicon::OutlinedCheckCircle,
                                EstadoAprobacionExcepcion::Rechazada => Heroicon::OutlinedXCircle,
                                default => Heroicon::OutlinedQuestionMarkCircle,
                            })
                            ->placeholder('—'),
                        TextEntry::make('areaAprobadora.area')
                            ->label('Área que aprueba')
                            ->icon(Heroicon::OutlinedBuildingOffice2)
                            ->badge()
                            ->color('gray')
                            ->placeholder('—'),
                        TextEntry::make('aprobador.name')
                            ->label('Aprobador designado')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->placeholder('—'),
                    ]),
                Section::make('Decisión')
                    ->description('Quién respondió, cuándo y con qué nota de respaldo.')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->visible(fn (SoftwareExc $record): bool => ! $record->estaPendiente())
                    ->schema([
                        TextEntry::make('decididoPor.name')
                            ->label('Decidido por')
                            ->icon(Heroicon::OutlinedUser)
                            ->weight(FontWeight::Medium)
                            ->placeholder('—'),
                        TextEntry::make('decidido_at')
                            ->label('Fecha de decisión')
                            ->icon(Heroicon::OutlinedCalendarDays)
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('comentario_decision')
                            ->label('Nota de decisión')
                            ->placeholder('Sin nota')
                            ->columnSpanFull()
                            ->visible(fn (SoftwareExc $record): bool => filled($record->comentario_decision)),
                    ]),
                Section::make('Registro')
                    ->icon(Heroicon::OutlinedClock)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 3])
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('updated_at')
                            ->label('Actualizado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('—'),
                        TextEntry::make('deleted_at')
                            ->label('Eliminado')
                            ->dateTime('d/m/Y H:i')
                            ->visible(fn (SoftwareExc $record): bool => $record->trashed()),
                    ]),
            ]);
    }

    private static function colorTipo(?string $tipo): string
    {
        return match ($tipo) {
            'Emergencia' => 'danger',
            'Permanente' => 'warning',
            'Temporal' => 'info',
            default => 'gray',
        };
    }
}
