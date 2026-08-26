{{-- Paleta corporativa como referencia rápida al elegir colores. --}}
<div>
    <p class="mb-2 text-[13px] font-medium text-gray-500 dark:text-gray-400">
        Paleta corporativa Capital SAFI
    </p>

    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-9">
        @foreach (config('tema.paleta_marca', []) as $color)
            <div class="flex flex-col gap-1">
                <div
                    class="h-8 rounded-lg ring-1 ring-gray-950/10 dark:ring-white/20"
                    style="background-color: {{ $color['hex'] }}"
                    title="{{ $color['nombre'] }} — {{ $color['hex'] }}"
                ></div>
                <span class="text-[10px] leading-3 text-gray-500 dark:text-gray-400">
                    {{ $color['nombre'] }}
                </span>
                <span class="font-mono text-[10px] leading-3 text-gray-400 dark:text-gray-500">
                    {{ $color['hex'] }}
                </span>
            </div>
        @endforeach
    </div>
</div>
