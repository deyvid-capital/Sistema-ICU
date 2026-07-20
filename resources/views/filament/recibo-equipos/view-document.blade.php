<div class="mx-auto w-full max-w-5xl space-y-3 text-center">
    @if (filled($url))
        <iframe
            src="{{ $url }}"
            class="mx-auto h-[70vh] w-full rounded-lg border border-gray-200 dark:border-gray-700"
            title="{{ $filename ?? 'Vista previa del documento' }}"
        ></iframe>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            No se encontró el documento para visualizar.
        </p>
    @endif
</div>
