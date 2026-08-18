<?php

namespace App\Filament\Imports;

use App\Filament\Imports\Concerns\HasImportGuide;
use Closure;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Arr;

class ImportColumnCatalog
{
    /**
     * @param  class-string<Importer>  $importerClass
     * @return array{
     *     summary: string|null,
     *     columns: list<array{
     *         name: string,
     *         header: string,
     *         label: string,
     *         example: string|null,
     *         required: bool,
     *         relationship: bool,
     *         helperText: string|null,
     *         sensitive: bool
     *     }>,
     *     required: list<array{
     *         name: string,
     *         header: string,
     *         label: string,
     *         example: string|null,
     *         required: bool,
     *         relationship: bool,
     *         helperText: string|null,
     *         sensitive: bool
     *     }>,
     *     optional: list<array{
     *         name: string,
     *         header: string,
     *         label: string,
     *         example: string|null,
     *         required: bool,
     *         relationship: bool,
     *         helperText: string|null,
     *         sensitive: bool
     *     }>,
     *     dependencies: list<array{
     *         name: string,
     *         header: string,
     *         label: string,
     *         example: string|null,
     *         required: bool,
     *         relationship: bool,
     *         helperText: string|null,
     *         sensitive: bool
     *     }>
     * }
     */
    public static function for(string $importerClass): array
    {
        $columns = array_map(
            self::describeColumn(...),
            $importerClass::getColumns(),
        );

        return [
            'summary' => self::summary($importerClass),
            'columns' => $columns,
            'required' => array_values(array_filter($columns, fn (array $column): bool => $column['required'])),
            'optional' => array_values(array_filter($columns, fn (array $column): bool => ! $column['required'])),
            'dependencies' => array_values(array_filter(
                $columns,
                fn (array $column): bool => $column['relationship'],
            )),
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     header: string,
     *     label: string,
     *     example: string|null,
     *     required: bool,
     *     relationship: bool,
     *     helperText: string|null,
     *     sensitive: bool
     * }
     */
    private static function describeColumn(ImportColumn $column): array
    {
        $example = Arr::first($column->getExamples());
        $sensitive = $column->isSensitive();

        return [
            'name' => $column->getName(),
            'header' => $column->getExampleHeader(),
            'label' => $column->getLabel() ?? $column->getName(),
            'example' => (! $sensitive && filled($example)) ? (string) $example : null,
            'required' => $column->isMappingRequired() || self::hasRequiredRule($column),
            'relationship' => $column->hasRelationship(),
            'helperText' => self::helperText($column),
            'sensitive' => $sensitive,
        ];
    }

    /**
     * @param  class-string<Importer>  $importerClass
     */
    private static function summary(string $importerClass): ?string
    {
        if (! in_array(HasImportGuide::class, class_uses_recursive($importerClass), true)) {
            return null;
        }

        return $importerClass::getImportGuideSummary();
    }

    private static function hasRequiredRule(ImportColumn $column): bool
    {
        $rules = Closure::bind(
            fn (): array => Arr::wrap($this->evaluate($this->dataValidationRules)),
            $column,
            ImportColumn::class,
        )();

        return in_array('required', $rules, true);
    }

    private static function helperText(ImportColumn $column): ?string
    {
        $value = Closure::bind(
            fn (): mixed => $this->evaluate($this->helperText),
            $column,
            ImportColumn::class,
        )();

        if ($value instanceof Htmlable) {
            $value = trim(strip_tags($value->toHtml()));
        }

        return filled($value) ? (string) $value : null;
    }
}
