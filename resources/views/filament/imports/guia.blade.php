@php
    use Filament\Support\Icons\Heroicon;
@endphp

<div class="space-y-4">
    @if (filled($guide['summary']))
        <x-filament::section
            heading="Cómo armar el CSV"
            :description="$guide['summary']"
            :icon="Heroicon::OutlinedInformationCircle"
            icon-color="info"
            compact
            
            secondary
        />
    @endif

    @if ($guide['required'] !== [])
        <x-filament::section
            heading="Columnas obligatorias"
            description="Deben ir en el archivo y mapearse al importar."
            :icon="Heroicon::OutlinedExclamationCircle"
            icon-color="danger"
            
            collapsible
            persist-collapsed
            divided
        >
            @foreach ($guide['required'] as $column)
                @include('filament.imports.guia-columna', [
                    'column' => $column,
                    'badgeLabel' => 'Obligatoria',
                    'badgeColor' => 'danger',
                ])
            @endforeach
        </x-filament::section>
    @endif

    @if ($guide['optional'] !== [])
        <x-filament::section
            heading="Columnas opcionales"
            description="Si no las trae el CSV, se omiten."
            :icon="Heroicon::OutlinedMinusCircle"
            icon-color="gray"
            compact
            collapsible
            persist-collapsed
            divided
        >
            @foreach ($guide['optional'] as $column)
                @include('filament.imports.guia-columna', [
                    'column' => $column,
                    'badgeLabel' => 'Opcional',
                    'badgeColor' => 'gray',
                ])
            @endforeach
        </x-filament::section>
    @endif

    @if ($guide['dependencies'] !== [])
        <x-filament::section
            heading="Dependencias"
            description="El valor debe coincidir con un registro que ya exista en el sistema."
            :icon="Heroicon::OutlinedLink"
            icon-color="warning"
            compact
            collapsible
            persist-collapsed
            divided
        >
            @foreach ($guide['dependencies'] as $column)
                @include('filament.imports.guia-columna', [
                    'column' => $column,
                    'badgeLabel' => 'Dependencia',
                    'badgeColor' => 'warning',
                ])
            @endforeach
        </x-filament::section>
    @endif
</div>
