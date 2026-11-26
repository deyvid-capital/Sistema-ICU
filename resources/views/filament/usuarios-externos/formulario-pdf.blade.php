@php
    /** @var \App\Models\UsuarioExterno $record */
    $record = $getRecord();
    $documento = $record->documentoOficial();
@endphp

@include('filament.recibo-equipos.view-document', [
    'url' => $documento?->storedDocumentUrl(),
    'filename' => $documento?->storedDocumentFilename(),
])
