@php
    use Filament\Support\Icons\Heroicon;

    $badgeColor ??= 'gray';
@endphp

<div class="flex flex-col gap-1.5 py-1">
    <div class="flex flex-wrap items-center gap-2">
        <span class="text-sm font-medium text-gray-950 dark:text-white">
            {{ $column['label'] }}
        </span>

        <x-filament::badge :color="$badgeColor" size="xs">
            {{ $badgeLabel }}
        </x-filament::badge>

        <x-filament::badge color="gray" size="sm">
            CSV: {{ $column['header'] }}
        </x-filament::badge>

        @if (filled($column['example']))
            <x-filament::badge color="info" size="sm">
                Ej. {{ $column['example'] }}
            </x-filament::badge>
        @endif

        @if ($column['relationship'])
            <x-filament::badge color="warning" size="sm" :icon="Heroicon::OutlinedLink">
                Debe existir
            </x-filament::badge>
        @endif
    </div>

    @if (filled($column['helperText']))
        <p class="text-xs leading-5 text-gray-600 dark:text-gray-400">
            {{ $column['helperText'] }}
        </p>
    @endif
</div>
