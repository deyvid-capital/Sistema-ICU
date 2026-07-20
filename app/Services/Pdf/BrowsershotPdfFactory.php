<?php

namespace App\Services\Pdf;

use Spatie\Browsershot\Browsershot;

class BrowsershotPdfFactory
{
    /**
     * Genera un PDF con Chrome headless (equivalente a Imprimir → Guardar como PDF).
     *
     * @param  view-string  $view
     * @param  array<string, mixed>  $data
     */
    public function fromView(string $view, array $data = []): string
    {
        $html = view($view, $data)->render();
        $html = $this->embedLocalLogo($html);

        return $this->make($html)->pdf();
    }

    private function make(string $html): Browsershot
    {
        $browsershot = Browsershot::html($html)
            ->setNodeModulePath(base_path('node_modules'))
            ->noSandbox()
            ->showBackground()
            ->format('Letter')
            // Equivale a @page { margin: 2cm 1cm 2cm 2.5cm }
            ->margins(20, 10, 20, 25)
            ->timeout(120);

        $chromePath = $this->resolveChromePath();

        if ($chromePath !== null) {
            $browsershot->setChromePath($chromePath);
        }

        return $browsershot;
    }

    private function resolveChromePath(): ?string
    {
        $configured = config('services.browsershot.chrome_path');

        if (is_string($configured) && $configured !== '' && is_file($configured)) {
            return $configured;
        }

        $home = getenv('HOME') ?: (string) ($_SERVER['HOME'] ?? '');
        $patterns = [
            $home.'/.cache/puppeteer/chrome-headless-shell/*/chrome-headless-shell-linux64/chrome-headless-shell',
            $home.'/.cache/puppeteer/chrome/*/chrome-linux64/chrome',
        ];

        foreach ($patterns as $pattern) {
            $matches = glob($pattern) ?: [];

            if ($matches !== []) {
                rsort($matches);

                return $matches[0];
            }
        }

        return null;
    }

    private function embedLocalLogo(string $html): string
    {
        $logoPath = public_path('images/Capital-Safi-SA-logo-.png');

        if (! is_file($logoPath)) {
            return $html;
        }

        $dataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));

        return str_replace(
            asset('images/Capital-Safi-SA-logo-.png'),
            $dataUri,
            $html
        );
    }
}
