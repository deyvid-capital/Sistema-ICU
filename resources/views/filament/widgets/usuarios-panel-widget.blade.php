<x-filament-widgets::widget class="fi-usuarios-panel-widget">
    <div class="flex flex-col gap-y-4">
        <x-filament::tabs label="Vistas del panel de usuarios">
            @foreach ($pestanas as $clave => $clase)
                <x-filament::tabs.item
                    :active="$activa === $clave"
                    :icon="$clase::getIcono()"
                    wire:click="$set('pestana', '{{ $clave }}')"
                    wire:key="usuarios-panel-tab-{{ $clave }}"
                >
                    {{ $clase::getEtiqueta() }}
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>

        @livewire($componente, key('usuarios-panel-' . $activa))
    </div>
</x-filament-widgets::widget>
