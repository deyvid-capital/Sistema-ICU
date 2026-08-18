<x-filament::section
    :aside="true"
    heading="Contraseña"
    description="Use una contraseña exclusiva para este sistema. Debe tener al menos 8 caracteres, combinar mayúsculas y minúsculas, e incluir un número."
>
    <form wire:submit.prevent="submit" class="space-y-6">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Después de cambiarla, las demás sesiones pueden cerrarse. Si alguien más conoce su contraseña actual, cámbiela ahora y revise las sesiones del navegador.
        </p>

        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit" form="submit">
                {{ __('filament-breezy::default.profile.password.submit.label') }}
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
