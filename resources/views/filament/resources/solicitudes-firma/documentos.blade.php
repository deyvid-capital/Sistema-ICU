@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\SolicitudFirmaDocumento> $documentos */
    $zona = config('app.display_timezone');
    $catalogo = app(\App\Services\Firma\DocumentosFirmables::class);
@endphp

<div class="flex flex-col gap-4">
    @foreach ($documentos as $documento)
        @php
            $firmas = $documento->firmas->sortBy(fn ($firma) => $firma->firmante->orden);
            $firmadas = $firmas->where('estado', \App\Enums\EstadoFirma::Firmado)->count();
        @endphp

        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
            {{-- Encabezado del documento --}}
            <div class="flex flex-wrap items-center gap-x-4 gap-y-3 bg-gray-50 px-4 py-3 dark:bg-white/5">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400">
                    <x-filament::icon icon="heroicon-o-document-text" class="size-6" />
                </div>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ $documento->nombre }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $catalogo->etiquetaOrigen($documento->origen_type) }}
                        · {{ $documento->totalPaginas() }} {{ $documento->totalPaginas() === 1 ? 'página' : 'páginas' }}
                        · {{ $firmadas }} de {{ $firmas->count() }} {{ $firmas->count() === 1 ? 'firma' : 'firmas' }}
                    </p>
                </div>

                <x-filament::badge :color="$documento->estado->getColor()">
                    {{ $documento->estado->getLabel() }}
                </x-filament::badge>

                <div class="flex items-center gap-2">
                    <x-filament::button
                        tag="a"
                        :href="route('firma.documentos.ver', $documento)"
                        target="_blank"
                        color="gray"
                        size="sm"
                        outlined
                        icon="heroicon-m-eye"
                    >
                        Ver
                    </x-filament::button>
                    <x-filament::button
                        tag="a"
                        :href="route('firma.documentos.descargar', $documento)"
                        color="gray"
                        size="sm"
                        outlined
                        icon="heroicon-m-arrow-down-tray"
                    >
                        Descargar
                    </x-filament::button>
                </div>
            </div>

            {{-- Firmas del documento --}}
            <ul class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($firmas as $firma)
                    <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-3 text-sm">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ $firma->firmante->orden }}
                        </span>

                        <span class="min-w-0 flex-1 truncate font-medium text-gray-950 dark:text-white">
                            {{ $firma->firmante->usuario?->name }}
                        </span>

                        <span class="text-gray-500 dark:text-gray-400">
                            @if ($firma->firmado_at)
                                Firmó el {{ $firma->firmado_at->timezone($zona)->format('d/m/Y \a \l\a\s H:i') }}
                            @elseif ($firma->motivo_rechazo)
                                <span class="text-danger-600 dark:text-danger-400">Motivo: {{ $firma->motivo_rechazo }}</span>
                            @endif
                        </span>

                        <x-filament::badge :color="$firma->estado->getColor()" size="sm">
                            {{ $firma->estado->getLabel() }}
                        </x-filament::badge>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
