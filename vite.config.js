import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                // Se carga bajo demanda con x-load en el canvas de posiciones de firma.
                'resources/js/firma/canvas-firmas.js',
            ],
            refresh: true,
            fonts: [
                bunny('Lato', {
                    weights: [400, 700],
                    subsets: ['latin', 'latin-ext'],
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        rolldownOptions: {
            // En modo app Vite descarta los export de las entradas; x-load necesita el
            // `export default` de resources/js/firma/canvas-firmas.js.
            preserveEntrySignatures: 'exports-only',
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
