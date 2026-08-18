<x-filament::section
    :aside="true"
    heading="Sesiones del navegador"
    description="Revise los dispositivos donde su cuenta está abierta y cierre los que no reconozca."
>
    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Esta lista muestra sesiones recientes. Si ve un dispositivo desconocido, cierre las demás sesiones y cambie su contraseña.
    </p>

    {{ $this->form }}

    <x-filament-actions::modals />
</x-filament::section>
