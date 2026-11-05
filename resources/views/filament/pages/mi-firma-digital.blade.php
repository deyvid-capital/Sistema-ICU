@php
    $enUso = $this->certificadoEnUso();
    $pendiente = $this->certificadoPendiente();
@endphp

<x-filament-panels::page>
    @if ($pendiente)
        <x-filament::section icon="heroicon-o-information-circle" icon-color="info">
            <x-slot name="heading">Tu firma digital está pendiente de activación</x-slot>
            <x-slot name="description">
                Un administrador cargó tu archivo .p12 el {{ $pendiente->created_at->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}
                y debe activarlo. No tienes que hacer nada: te avisaremos cuando esté lista.
            </x-slot>
        </x-filament::section>
    @endif

    @if ($enUso?->estado === \App\Enums\EstadoCertificadoFirma::RequiereReactivacion)
        <x-filament::section icon="heroicon-o-exclamation-triangle" icon-color="warning">
            <x-slot name="heading">Tu firma está en pausa</x-slot>
            <x-slot name="description">
                @if ($enUso->puedeVincularse())
                    Tu contraseña del sistema fue restablecida. Presiona <strong>Reactivar mi firma</strong> y escribe tu contraseña actual del sistema
                    (también se reactiva sola la próxima vez que inicies sesión).
                @else
                    Tu contraseña del sistema fue restablecida. Un administrador debe reactivar tu firma; ya se le avisó.
                @endif
            </x-slot>
        </x-filament::section>
    @elseif ($enUso?->estaPendienteDeVincular())
        <x-filament::section icon="heroicon-o-information-circle" icon-color="info">
            <x-slot name="heading">Tu firma está lista</x-slot>
            <x-slot name="description">
                Un administrador la activó. Firmarás con tu contraseña del sistema: se vincula sola al firmar o al iniciar sesión,
                o ahora mismo con <strong>Vincular mi firma</strong>.
            </x-slot>
        </x-filament::section>
    @endif

    @if ($enUso)
        <x-filament::section icon="heroicon-o-finger-print">
            <x-slot name="heading">Mi firma digital</x-slot>
            <x-slot name="afterHeader">
                <x-filament::badge :color="$enUso->estado->getColor()">
                    {{ $enUso->estado->getLabel() }}
                </x-filament::badge>
            </x-slot>

            <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                @foreach ([
                    'Titular' => $enUso->titular_cn,
                    'Documento' => trim(($enUso->titular_tipo_documento ?? '').' '.($enUso->titular_documento ?? '')),
                    'Emisor' => $enUso->emisor,
                    'Vigencia' => $enUso->vigente_desde?->format('d/m/Y').' – '.$enUso->vigente_hasta?->format('d/m/Y'),
                    'Nombre en el sello' => $enUso->nombre_en_sello,
                    'Cargo en el sello' => $enUso->cargo_en_sello,
                ] as $etiqueta => $valor)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">{{ $etiqueta }}</dt>
                        <dd class="font-medium text-gray-950 dark:text-white">{{ filled($valor) ? $valor : '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::section>
    @elseif (! $pendiente)
        <x-filament::section icon="heroicon-o-finger-print">
            <x-slot name="heading">No tienes una firma digital asignada</x-slot>
            <x-slot name="description">
                Solicita a un administrador que cargue tu archivo .p12 en Configuración → Firmas digitales.
            </x-slot>
        </x-filament::section>
    @endif
</x-filament-panels::page>
