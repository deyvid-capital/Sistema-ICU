<?php

use App\Http\Controllers\Archivos\DocumentoPdfController;
use App\Http\Controllers\Firma\DocumentoFirmaController;
use App\Http\Controllers\MovimientoFormularioPdfController;
use App\Http\Controllers\PdfPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
Route::get('/api/ver-form-entrega', function () {
    return view('PlantillasPdf.FormEntrega');
});

Route::get('/api/ver-form-recepcion', function () {
    return view('PlantillasPdf.FormRecepcion');
});

Route::get('/api/ver-form-nota-salida', function () {
    return view('PlantillasPdf.FormNotaSalida');
});

Route::get('/api/ver-form-nota-recepcion-externa', function () {
    return view('PlantillasPdf.FormRecepcionExterna');
});

Route::get('/api/ver-form-alta-baja', function () {
    return view('PlantillasPdf.FormAltaBaja');
});

Route::get('/api/ver-lista-usuarios', function () {
    return view('PlantillasPdf.ListaUsuarios');
});
*/

Route::middleware('auth')->group(function () {
    Route::get('/movimientos/{movimiento}/preview-alta-baja', [MovimientoFormularioPdfController::class, 'previewHtml'])
        ->name('movimientos.preview-alta-baja');
});

Route::prefix('pdf/preview')->middleware('auth')->name('pdf.preview.')->group(function () {
    Route::get('/entrega', [PdfPreviewController::class, 'entrega'])->name('entrega');
    Route::get('/recepcion', [PdfPreviewController::class, 'recepcion'])->name('recepcion');
    Route::get('/nota-salida', [PdfPreviewController::class, 'notaSalida'])->name('nota-salida');
    Route::get('/recepcion-externa', [PdfPreviewController::class, 'recepcionExterna'])->name('recepcion-externa');
    Route::get('/alta-baja', [MovimientoFormularioPdfController::class, 'previewPdf'])->name('alta-baja');
});

Route::prefix('archivos/pdf')->middleware('auth')->name('archivos.pdf.')
    ->whereIn('tipo', array_keys(DocumentoPdfController::TIPOS))
    ->whereNumber('id')
    ->group(function () {
        Route::get('/{tipo}/{id}/ver', [DocumentoPdfController::class, 'ver'])->name('ver');
        Route::get('/{tipo}/{id}/descargar', [DocumentoPdfController::class, 'descargar'])->name('descargar');
    });

Route::prefix('firma/documentos')->middleware('auth')->name('firma.documentos.')->group(function () {
    Route::get('/{documento}/ver', [DocumentoFirmaController::class, 'ver'])->name('ver');
    Route::get('/{documento}/descargar', [DocumentoFirmaController::class, 'descargar'])->name('descargar');
    Route::get('/envio/{solicitud}/zip', [DocumentoFirmaController::class, 'zip'])->name('zip');
});
