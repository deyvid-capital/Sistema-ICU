<?php

namespace App\Filament\Actions;

use App\Filament\Imports\CsvEncodingDetector;
use App\Filament\Imports\ImportColumnCatalog;
use Filament\Actions\ImportAction;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;

class GuiaImportAction extends ImportAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->slideOver();
        $this->modalWidth(Width::TwoExtraLarge);
        $this->modalContent(fn (): View => view('filament.imports.guia', [
            'guide' => ImportColumnCatalog::for($this->getImporter()),
        ]));
    }

    protected function detectCsvEncoding(mixed $resource): ?string
    {
        return CsvEncodingDetector::fromStream($resource);
    }
}
