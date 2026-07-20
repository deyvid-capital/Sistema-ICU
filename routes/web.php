<?php

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

Route::get('/api/ver-form-nota-salida-equipos', function () {
    return view('PlantillasPdf.FormRecepcionExterna');
});
