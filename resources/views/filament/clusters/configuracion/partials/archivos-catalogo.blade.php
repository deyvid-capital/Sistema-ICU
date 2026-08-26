@php
    $servicio = app(\App\Services\Archivos\ArchivosService::class);
    $prefijo = $servicio->prefijo();
    $disco = $servicio->disco();
@endphp

<div class="space-y-6 text-sm">
    <div>
        <p class="mb-2 font-medium text-gray-950 dark:text-white">Colecciones de imagen (Media Library)</p>
        <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
            @foreach ($servicio->coleccionesImagen() as $coleccion => $meta)
                <li class="flex flex-col gap-1 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                    <span class="font-medium text-gray-950 dark:text-white">{{ $meta['etiqueta'] }}</span>
                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">
                        {{ $disco }}:{{ $prefijo }}/{{ $coleccion }}/{{ $meta['modelo'] }}/…
                        @if (! empty($meta['conversiones']))
                            · thumb/webp
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    <div>
        <p class="mb-2 font-medium text-gray-950 dark:text-white">Carpetas PDF (sin Media Library)</p>
        <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-gray-700 dark:border-gray-700">
            @foreach ($servicio->carpetasPdf() as $clave => $meta)
                <li class="flex flex-col gap-1 px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                    <span class="font-medium text-gray-950 dark:text-white">{{ $meta['etiqueta'] }}</span>
                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400">
                        {{ $disco }}:{{ $meta['directorio'] }}/
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
