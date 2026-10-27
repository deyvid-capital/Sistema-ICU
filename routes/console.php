<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| Firma digital (Docs/firma-digital/06-notificaciones.md). El servicio `scheduler`
| de compose.yaml corre `schedule:work`.
*/
Schedule::command('firmas:enviar-recordatorios')->hourly()->withoutOverlapping();
Schedule::command('firmas:avisar-vencimientos')->dailyAt('08:00')->timezone('America/La_Paz')->withoutOverlapping();
