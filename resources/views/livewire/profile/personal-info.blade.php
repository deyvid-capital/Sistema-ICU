<x-filament::section
    :aside="true"
    icon="heroicon-o-user"
    heading="Información personal"
    description="Correo y carnet no se editan aquí."
>
    <form wire:submit.prevent="submit" class="space-y-6">
        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit" form="submit">
                Guardar cambios
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
