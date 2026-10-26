@php
    $datos = $this->datosCanvas();
@endphp

<x-filament-panels::page>
    {{-- Función vacía para inicializar Alpine hasta que `x-load` traiga el componente (igual que los gráficos de Filament). --}}
    <div x-data="{ canvasFirmas() {} }">
        <div
            x-load
            x-load-src="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/firma/canvas-firmas.js') }}"
            wire:ignore
            x-data="canvasFirmas({
                posiciones: $wire.$entangle('posiciones'),
                documentos: @js($datos['documentos']),
                firmantes: @js($datos['firmantes']),
                recuadro: @js($datos['recuadro']),
                sugeridas: @js($datos['sugeridas']),
            })"
            x-on:pointermove.window="arrastrar($event)"
            x-on:pointerup.window="terminarArrastre()"
            class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]"
        >
            {{-- Panel lateral --}}
            <div class="flex flex-col gap-4 lg:sticky lg:top-4 lg:self-start">
                <x-filament::section>
                    <x-slot name="heading">Firmantes</x-slot>
                    <x-slot name="description">Elige uno y haz clic en el documento.</x-slot>

                    <div class="flex flex-col gap-2">
                        <template x-for="firmante in firmantes" :key="firmante.id">
                            <button
                                type="button"
                                x-on:click="firmanteActivo = firmante.id"
                                class="flex items-center gap-3 rounded-lg border px-3 py-2 text-left text-sm transition"
                                :class="firmanteActivo === firmante.id ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' : 'border-gray-200 dark:border-white/10'"
                            >
                                <span class="size-3 shrink-0 rounded-full" :style="`background:${firmante.color}`"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium text-gray-950 dark:text-white" x-text="firmante.nombre"></span>
                                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400" x-text="firmante.cargo ?? ''"></span>
                                </span>
                                <template x-if="posicion(documentoActivo, firmante.id)">
                                    <span class="flex items-center gap-1">
                                        <x-filament::icon icon="heroicon-m-check-circle" class="size-5 text-success-600" />
                                        <span
                                            role="button"
                                            title="Quitar recuadro"
                                            x-on:click.stop="quitar(firmante.id)"
                                            class="text-gray-400 hover:text-danger-600"
                                        >
                                            <x-filament::icon icon="heroicon-m-x-mark" class="size-4" />
                                        </span>
                                    </span>
                                </template>
                            </button>
                        </template>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Documentos</x-slot>

                    <div class="flex flex-col gap-2">
                        <template x-for="documento in documentos" :key="documento.id">
                            <button
                                type="button"
                                x-on:click="documentoActivo = documento.id"
                                class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-left text-sm"
                                :class="documentoActivo === documento.id ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' : 'border-gray-200 dark:border-white/10'"
                            >
                                <span class="truncate" x-text="documento.nombre"></span>
                                <span
                                    class="shrink-0 text-xs font-medium"
                                    :class="documentoCompleto(documento.id) ? 'text-success-600' : 'text-gray-500'"
                                    x-text="`${ubicadosEn(documento.id)}/${firmantes.length}`"
                                ></span>
                            </button>
                        </template>
                    </div>

                    <div class="mt-4 flex flex-col gap-2">
                        <x-filament::button color="gray" size="sm" icon="heroicon-m-document-duplicate" x-show="documentos.length > 1" x-on:click="copiarATodos()">
                            Copiar posiciones a los demás documentos
                        </x-filament::button>
                        <x-filament::button color="gray" size="sm" icon="heroicon-m-clock" x-show="sugeridas.length > 0" x-on:click="usarSugeridas()">
                            Usar posiciones de la última vez
                        </x-filament::button>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Zoom</x-slot>

                    <x-filament::input.wrapper>
                        <x-filament::input.select x-model="zoom">
                            <option value="ancho">Ajustar al ancho</option>
                            <option value="1">100 %</option>
                            <option value="1.5">150 %</option>
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </x-filament::section>
            </div>

            {{-- Documento --}}
            <div class="min-w-0">
                <p x-show="cargando" class="mb-2 text-sm text-gray-500">Cargando documento…</p>
                <p x-show="error" x-text="error" class="mb-2 text-sm text-danger-600"></p>

                <div x-ref="paginas" class="overflow-x-auto rounded-xl bg-gray-100 p-2 dark:bg-white/5">
                    <template x-for="pagina in paginas" :key="`${documentoActivo}-${pagina.numero}`">
                        <div
                            class="relative mx-auto mb-4 cursor-crosshair bg-white shadow"
                            :style="estiloPagina(pagina)"
                            x-on:click="colocar($event, pagina)"
                        >
                            <canvas :data-pagina="pagina.numero" class="block"></canvas>

                            <template x-for="recuadroFirma in recuadrosDePagina(pagina.numero)" :key="recuadroFirma.firmante_id">
                                <div
                                    class="absolute flex cursor-move touch-none select-none flex-col items-center justify-end overflow-hidden rounded border-2 border-dashed p-1 text-center leading-tight text-gray-900"
                                    :style="estiloRecuadro(recuadroFirma, pagina)"
                                    x-on:pointerdown="iniciarArrastre($event, recuadroFirma, pagina)"
                                    x-on:click.stop
                                >
                                    <span class="font-bold">Firmado digitalmente por:</span>
                                    <span class="truncate" x-text="firmante(recuadroFirma.firmante_id)?.nombre"></span>
                                    <span class="truncate" x-text="firmante(recuadroFirma.firmante_id)?.cargo ?? ''"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
