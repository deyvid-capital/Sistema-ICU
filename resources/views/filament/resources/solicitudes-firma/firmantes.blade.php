@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\SolicitudFirmaFirmante> $firmantes */
@endphp

<ul class="divide-y divide-gray-200 overflow-hidden rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10">
    @foreach ($firmantes as $firmante)
        @php
            $total = $firmante->firmas->count();
            $firmadas = $firmante->firmas->where('estado', \App\Enums\EstadoFirma::Firmado)->count();
            $rechazadas = $firmante->firmas->where('estado', \App\Enums\EstadoFirma::Rechazado)->count();
            $color = match (true) {
                $rechazadas > 0 => 'danger',
                $total > 0 && $firmadas === $total => 'success',
                default => 'warning',
            };
        @endphp

        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-4 py-3 text-sm">
            <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                {{ $firmante->orden }}
            </span>

            <div class="min-w-0 flex-1">
                <p class="truncate font-medium text-gray-950 dark:text-white">{{ $firmante->usuario?->name }}</p>
                @if ($cargo = $firmante->usuario?->certificadoFirmaActivo?->cargo_en_sello)
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $cargo }}</p>
                @endif
            </div>

            <x-filament::badge :color="$color" size="sm">
                Firmó {{ $firmadas }} de {{ $total }}
            </x-filament::badge>
        </li>
    @endforeach
</ul>
