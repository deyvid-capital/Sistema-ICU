<?php

use App\Http\Controllers\MovimientoFormularioPdfController;
use App\Http\Controllers\PdfPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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
