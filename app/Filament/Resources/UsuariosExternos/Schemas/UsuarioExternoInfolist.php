<?php

namespace App\Filament\Resources\UsuariosExternos\Schemas;

use App\Filament\Resources\Concerns\HasRegistroInfolistSection;
use App\Models\UsuarioExterno;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;

class UsuarioExternoInfolist
{
    use HasRegistroInfolistSection;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Contacto externo')
                    ->icon(Heroicon::OutlinedUser)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('numero')
                            ->label('N° de formulario')
                            ->fontFamily('mono')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('nombre_contacto')
                            ->label('Nombre del contacto')
                            ->weight(FontWeight::Bold),
                        TextEntry::make('empresa')
                            ->label('Empresa'),
                        TextEntry::make('cargo')
                            ->label('Cargo'),
                        TextEntry::make('correo')
                            ->label('Correo')
                            ->icon(Heroicon::OutlinedEnvelope)
                            ->copyable(),
                    ]),
                Section::make('Acceso')
                    ->icon(Heroicon::OutlinedShare)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('responsable.name')
                            ->label('Responsable interno')
                            ->placeholder('—'),
                        TextEntry::make('motivo')
                            ->label('Motivo'),
                        TextEntry::make('permiso')
                            ->label('Permiso sobre los documentos')
                            ->badge(),
                        TextEntry::make('fecha_inicio')
                            ->label('Fecha inicio')
                            ->date('d/m/Y'),
                        TextEntry::make('fecha_fin')
                            ->label('Fecha fin')
                            ->date('d/m/Y')
                            ->placeholder('Sin fecha fin'),
                        IconEntry::make('estado')
                            ->label('Activo')
                            ->boolean(),
                        TextEntry::make('recordatorio')
                            ->label('Aviso de fecha fin')
                            ->state(fn (UsuarioExterno $record): string => $record->estadoRecordatorio())
                            ->icon(Heroicon::OutlinedBellAlert)
                            ->columnSpanFull(),
                    ]),
                Section::make('Documentos compartidos')
                    ->icon(Heroicon::OutlinedFolderOpen)
                    ->columnSpanFull()
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('sitioSharepoint.nombre_sitio')
                            ->label('Sitio SharePoint')
                            ->url(fn (UsuarioExterno $record): ?string => $record->sitioSharepoint?->url, shouldOpenInNewTab: true)
                            ->placeholder('—'),
                        TextEntry::make('onedrive')
                            ->label('OneDrive')
                            ->url(fn (UsuarioExterno $record): ?string => str_starts_with((string) $record->onedrive, 'http') ? $record->onedrive : null, shouldOpenInNewTab: true)
                            ->placeholder('—'),
                    ]),
                Section::make('Formulario de Usuario Externo')
                    ->description(fn (UsuarioExterno $record): ?string => $record->documentoOficial()?->etiquetaEstadoFirma())
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->columnSpanFull()
                    ->visible(fn (UsuarioExterno $record): bool => $record->hasDocumentoOficial())
                    ->schema([
                        ViewEntry::make('formulario_pdf')
                            ->hiddenLabel()
                            ->view('filament.usuarios-externos.formulario-pdf'),
                    ]),
                static::registroInfolistSection(),
            ]);
    }
}
