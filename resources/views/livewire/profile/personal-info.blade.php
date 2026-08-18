<x-filament::section
    :aside="true"
    heading="Información personal"
    description="Actualice sus datos de contacto. El correo y el carnet no pueden modificarse desde aquí."
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
