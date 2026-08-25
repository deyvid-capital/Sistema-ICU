{{--
    Simulación del panel con los colores del formulario. Los tokens vienen de
    AparienciaService, así que el texto y el ítem activo que se ven aquí son
    exactamente los que calculará el CSS real.
--}}
@php
    $vistaPrevia = $this->vistaPrevia();
    $primario = $this->data['color_primario'] ?? '#003973';

    $modos = [
        ['clave' => 'claro', 'titulo' => 'Modo claro', 'fondo' => '#F8FAFC', 'texto' => '#CBD5E1'],
        ['clave' => 'oscuro', 'titulo' => 'Modo oscuro', 'fondo' => '#0F1419', 'texto' => '#334155'],
    ];
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    @foreach ($modos as $modo)
        @php
            $sidebar = $vistaPrevia[$modo['clave']]['sidebar'];
            $topbar = $vistaPrevia[$modo['clave']]['topbar'];
        @endphp

        <div>
            <p class="mb-2 text-[13px] font-medium text-gray-500 dark:text-gray-400">
                {{ $modo['titulo'] }}
            </p>

            <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/10 dark:ring-white/10">
                <div class="flex h-44">
                    {{-- Sidebar --}}
                    <div
                        class="flex w-28 shrink-0 flex-col gap-1 p-2"
                        style="background-color: {{ $sidebar['bg'] }}; color: {{ $sidebar['fg_hex'] }}"
                    >
                        <div class="mb-1 flex h-7 items-center px-1">
                            <span
                                class="h-2.5 w-16 rounded"
                                style="background-color: color-mix(in oklab, {{ $sidebar['fg_hex'] }} 45%, transparent)"
                            ></span>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-md px-1.5 py-1">
                            <span class="size-2 rounded-full" style="background-color: color-mix(in oklab, {{ $sidebar['fg_hex'] }} 60%, transparent)"></span>
                            <span class="h-1.5 flex-1 rounded" style="background-color: color-mix(in oklab, {{ $sidebar['fg_hex'] }} 40%, transparent)"></span>
                        </div>

                        {{-- Ítem activo --}}
                        <div
                            class="flex items-center gap-1.5 rounded-md px-1.5 py-1"
                            style="background-color: color-mix(in oklab, {{ $sidebar['fg_hex'] }} 13%, transparent)"
                        >
                            <span class="size-2 rounded-full" style="background-color: {{ $sidebar['activo_hex'] }}"></span>
                            <span class="h-1.5 flex-1 rounded" style="background-color: {{ $sidebar['activo_hex'] }}"></span>
                        </div>

                        <div class="flex items-center gap-1.5 rounded-md px-1.5 py-1">
                            <span class="size-2 rounded-full" style="background-color: color-mix(in oklab, {{ $sidebar['fg_hex'] }} 60%, transparent)"></span>
                            <span class="h-1.5 flex-1 rounded" style="background-color: color-mix(in oklab, {{ $sidebar['fg_hex'] }} 40%, transparent)"></span>
                        </div>

                        <div class="mt-auto flex items-center gap-1.5 rounded-md px-1.5 py-1">
                            <span class="size-3 rounded-full" style="background-color: color-mix(in oklab, {{ $sidebar['fg_hex'] }} 30%, transparent)"></span>
                            <span class="h-1.5 flex-1 rounded" style="background-color: color-mix(in oklab, {{ $sidebar['fg_hex'] }} 40%, transparent)"></span>
                        </div>
                    </div>

                    {{-- Contenido --}}
                    <div class="flex flex-1 flex-col" style="background-color: {{ $modo['fondo'] }}">
                        <div
                            class="flex h-8 shrink-0 items-center gap-2 px-2"
                            style="background-color: {{ $topbar['bg'] }}; color: {{ $topbar['fg_hex'] }}"
                        >
                            <span
                                class="h-3.5 flex-1 rounded"
                                style="background-color: color-mix(in oklab, {{ $topbar['fg_hex'] }} 10%, transparent)"
                            ></span>
                            <span class="size-2 rounded-full" style="background-color: color-mix(in oklab, {{ $topbar['fg_hex'] }} 55%, transparent)"></span>
                            <span class="size-2 rounded-full" style="background-color: color-mix(in oklab, {{ $topbar['fg_hex'] }} 55%, transparent)"></span>
                        </div>

                        <div class="flex flex-1 flex-col gap-2 p-3">
                            <span class="h-2 w-2/3 rounded" style="background-color: {{ $modo['texto'] }}"></span>
                            <span class="h-2 w-1/2 rounded" style="background-color: {{ $modo['texto'] }}"></span>

                            <span
                                class="mt-auto inline-flex h-6 w-24 items-center justify-center rounded-lg text-[10px] font-medium text-white"
                                style="background-color: {{ $primario }}"
                            >
                                Botón
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
