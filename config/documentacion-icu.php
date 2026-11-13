<?php

use App\Models\User;
use App\Support\Guia\AnfitrionIcu;

/*
| Guía de usuario (paquete deyvid-capital/documentacion-sistema-icu).
| El contenido, las pantallas y el PDF viven en el paquete; aquí solo se
| indica cómo se conecta con este sistema.
*/
return [

    'anfitrion' => AnfitrionIcu::class,

    'modelo_usuario' => User::class,

    /*
    | Datos ficticios para las capturas (guia:capturar). Los valores deben
    | coincidir con las constantes de Database\Seeders\GuiaDemoSeeder.
    */
    'demo' => [
        'seeder' => 'Database\\Seeders\\GuiaDemoSeeder',
        'base_de_datos' => 'guia_demo',
        'email' => 'guia.demo@example.com',
        'password' => 'guia-demo-2026',
        // Reloj detenido en el servidor de capturas (misma fecha HOY del seeder).
        // Formato: Y-m-d H:i:s — solo lo usa guia:capturar vía GUIA_FECHA_FIJA.
        'fecha' => '2026-06-15 10:00:00',
    ],

    'pdf' => [
        'dias_de_vigencia' => 30,
        'chrome' => env('BROWSERSHOT_CHROME_PATH'),
    ],

];
