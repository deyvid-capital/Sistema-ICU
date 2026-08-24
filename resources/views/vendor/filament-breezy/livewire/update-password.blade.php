<x-filament::section
    :aside="true"
    heading="Contraseña"
    description="Mínimo 8 caracteres, con mayúsculas, minúsculas y un número."
>
    <form wire:submit.prevent="submit" class="space-y-6">
        {{ $this->form }}

        <div class="text-right">
            <x-filament::button type="submit" form="submit">
                {{ __('filament-breezy::default.profile.password.submit.label') }}
            </x-filament::button>
        </div>
    </form>
</x-filament::section>
