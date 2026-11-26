<?php

namespace App\Filament\Forms\Components;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Enums\IconSize;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\ComponentAttributeBag;
use LogicException;

use function Filament\Support\generate_icon_html;

/**
 * Select propio con gestión de opciones (crear, editar y eliminar) desde el desplegable.
 *
 * Es un Field independiente: no extiende ni envuelve Filament\Forms\Components\Select.
 * Reutiliza solo las clases CSS del tema de Filament (`fi-select-input`, `fi-dropdown-*`,
 * `fi-badge`), así que se ve igual que el Select nativo en claro y oscuro.
 *
 * - Selección única por defecto; `->multiple()` para varias, con badges removibles.
 * - Opciones con `->options()` o desde un modelo con `->optionsFromModel()`.
 * - «+ Crear» en la cabecera del desplegable y menú ⋮ por opción con Editar y Eliminar.
 *   Las tres son acciones de Filament registradas en el campo (modales, validación y
 *   confirmación nativos) y se autorizan en el servidor, no solo en la vista.
 *
 * Vista: resources/views/filament/forms/components/crud-select.blade.php
 * Lógica de Alpine: resources/js/filament/forms/crud-select.js
 * Documentación: .Doc_Laravel_13/Documentacion Sistema/Componente-CrudSelect/README.md
 */
class CrudSelect extends Field
{
    protected string $view = 'filament.forms.components.crud-select';

    /**
     * @var array<string | int, string> | Arrayable<string | int, string> | class-string<BackedEnum> | Closure | null
     */
    protected array|Arrayable|string|Closure|null $options = null;

    protected bool|Closure $isMultiple = false;

    protected bool|Closure $isSearchable = true;

    protected bool|Closure|null $isClearable = null;

    protected string|Closure|null $placeholder = null;

    protected string|Closure|null $noOptionsMessage = null;

    protected string|Closure|null $noSearchResultsMessage = null;

    /**
     * Ícono por opción: uno para todas, un mapa valor => ícono o un Closure($value, $label).
     *
     * @var string | BackedEnum | array<string | int, string | BackedEnum | null> | Closure | null
     */
    protected string|BackedEnum|array|Closure|null $optionIcon = null;

    /** @var class-string<Model> | null */
    protected ?string $optionModel = null;

    protected ?string $optionTitleAttribute = null;

    protected ?Closure $modifyOptionsQueryUsing = null;

    /** @var array<Component | Action> | Closure | null */
    protected array|Closure|null $createOptionForm = null;

    /** @var array<Component | Action> | Closure | null */
    protected array|Closure|null $editOptionForm = null;

    protected ?Closure $createOptionUsing = null;

    protected ?Closure $fillEditOptionFormUsing = null;

    protected ?Closure $updateOptionUsing = null;

    protected ?Closure $deleteOptionUsing = null;

    protected bool|Closure|null $canCreateOption = null;

    protected bool|Closure|null $canEditOption = null;

    protected bool|Closure|null $canDeleteOption = null;

    protected string|Closure|null $createOptionModalHeading = null;

    protected string|Closure|null $editOptionModalHeading = null;

    /**
     * Opciones ya resueltas durante esta petición (evita repetir la consulta).
     *
     * @var array<string, string> | null
     */
    protected ?array $cachedOptions = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default(static fn (CrudSelect $component): mixed => $component->isMultiple() ? [] : null);

        // El estado siempre llega como lista en múltiple y como escalar o null en simple.
        $this->afterStateHydrated(static function (CrudSelect $component, mixed $state): void {
            if ($component->isMultiple()) {
                $component->state(array_values(array_filter(
                    is_array($state) ? $state : (blank($state) ? [] : [$state]),
                    fn (mixed $value): bool => filled($value),
                )));

                return;
            }

            if (is_array($state)) {
                $component->state($state[0] ?? null);
            }
        });

        $this->rule(static fn (CrudSelect $component): ?string => $component->isMultiple() ? 'array' : null);

        // Solo se aceptan valores que existen entre las opciones (como el Select nativo).
        $this->rule(static fn (CrudSelect $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            if (blank($value)) {
                return;
            }

            $validos = array_map('strval', array_keys($component->getOptions()));

            foreach ((array) $value as $item) {
                if (! in_array((string) $item, $validos, true)) {
                    $fail(__('validation.in', ['attribute' => $component->getValidationAttribute()]));

                    return;
                }
            }
        });

        $this->registerActions([
            static fn (CrudSelect $component): ?Action => $component->getCreateOptionAction(),
            static fn (CrudSelect $component): ?Action => $component->getEditOptionAction(),
            static fn (CrudSelect $component): ?Action => $component->getDeleteOptionAction(),
        ]);
    }

    // ------------------------------------------------------------------
    // Configuración básica (paridad con el Select de Filament)
    // ------------------------------------------------------------------

    /**
     * @param  array<string | int, string> | Arrayable<string | int, string> | class-string<BackedEnum> | Closure | null  $options
     */
    public function options(array|Arrayable|string|Closure|null $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function searchable(bool|Closure $condition = true): static
    {
        $this->isSearchable = $condition;

        return $this;
    }

    /**
     * Muestra el botón para limpiar la selección. Por defecto: solo si el campo no es obligatorio.
     */
    public function clearable(bool|Closure $condition = true): static
    {
        $this->isClearable = $condition;

        return $this;
    }

    public function placeholder(string|Closure|null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function noOptionsMessage(string|Closure|null $message): static
    {
        $this->noOptionsMessage = $message;

        return $this;
    }

    public function noSearchResultsMessage(string|Closure|null $message): static
    {
        $this->noSearchResultsMessage = $message;

        return $this;
    }

    /**
     * Ícono de cada opción.
     *
     * - Un ícono para todas: `->optionIcon(Heroicon::OutlinedTag)`.
     * - Un mapa: `->optionIcon(['pdf' => Heroicon::OutlinedDocument, ...])`.
     * - Un Closure: `->optionIcon(fn (string $value, string $label, ?Model $record) => ...)`.
     *   `$record` llega solo con `optionsFromModel()`.
     *
     * @param  string | BackedEnum | array<string | int, string | BackedEnum | null> | Closure | null  $icon
     */
    public function optionIcon(string|BackedEnum|array|Closure|null $icon): static
    {
        $this->optionIcon = $icon;

        return $this;
    }

    // ------------------------------------------------------------------
    // Opciones desde un modelo Eloquent (atajo para el CRUD completo)
    // ------------------------------------------------------------------

    /**
     * Toma las opciones de un modelo y deja listos Crear, Editar y Eliminar contra él.
     *
     * Los formularios de crear y editar se siguen declarando con createOptionForm() y
     * editOptionForm(). Los permisos por defecto salen de la policy del modelo
     * (create, update y delete). Cualquier callback declarado a mano tiene prioridad.
     *
     * @param  class-string<Model>  $model
     */
    public function optionsFromModel(string $model, string $titleAttribute, ?Closure $modifyQueryUsing = null): static
    {
        if (! is_subclass_of($model, Model::class)) {
            throw new LogicException("[{$model}] debe ser un modelo Eloquent.");
        }

        $this->optionModel = $model;
        $this->optionTitleAttribute = $titleAttribute;
        $this->modifyOptionsQueryUsing = $modifyQueryUsing;

        return $this;
    }

    // ------------------------------------------------------------------
    // Crear, editar y eliminar opciones
    // ------------------------------------------------------------------

    /**
     * @param  array<Component | Action> | Closure | null  $schema
     */
    public function createOptionForm(array|Closure|null $schema): static
    {
        $this->createOptionForm = $schema;

        return $this;
    }

    /**
     * Guarda la opción nueva y devuelve su valor (clave). Recibe `array $data`.
     */
    public function createOptionUsing(?Closure $callback): static
    {
        $this->createOptionUsing = $callback;

        return $this;
    }

    /**
     * @param  array<Component | Action> | Closure | null  $schema
     */
    public function editOptionForm(array|Closure|null $schema): static
    {
        $this->editOptionForm = $schema;

        return $this;
    }

    /**
     * Datos con que se llena el formulario de edición. Recibe `string $value`.
     */
    public function fillEditOptionFormUsing(?Closure $callback): static
    {
        $this->fillEditOptionFormUsing = $callback;

        return $this;
    }

    /**
     * Guarda los cambios de una opción. Recibe `array $data` y `string $value`.
     */
    public function updateOptionUsing(?Closure $callback): static
    {
        $this->updateOptionUsing = $callback;

        return $this;
    }

    /**
     * Elimina una opción. Recibe `string $value`. Se pide confirmación antes de llamarlo.
     */
    public function deleteOptionUsing(?Closure $callback): static
    {
        $this->deleteOptionUsing = $callback;

        return $this;
    }

    public function canCreateOption(bool|Closure $condition = true): static
    {
        $this->canCreateOption = $condition;

        return $this;
    }

    /**
     * @param  bool | Closure  $condition  Closure($value, ?Model $record): bool
     */
    public function canEditOption(bool|Closure $condition = true): static
    {
        $this->canEditOption = $condition;

        return $this;
    }

    /**
     * @param  bool | Closure  $condition  Closure($value, ?Model $record): bool
     */
    public function canDeleteOption(bool|Closure $condition = true): static
    {
        $this->canDeleteOption = $condition;

        return $this;
    }

    public function createOptionModalHeading(string|Closure|null $heading): static
    {
        $this->createOptionModalHeading = $heading;

        return $this;
    }

    public function editOptionModalHeading(string|Closure|null $heading): static
    {
        $this->editOptionModalHeading = $heading;

        return $this;
    }

    // ------------------------------------------------------------------
    // Getters
    // ------------------------------------------------------------------

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function isSearchable(): bool
    {
        return (bool) $this->evaluate($this->isSearchable);
    }

    public function isClearable(): bool
    {
        if ($this->isDisabled()) {
            return false;
        }

        return (bool) ($this->evaluate($this->isClearable) ?? ! $this->isRequired());
    }

    public function getPlaceholder(): ?string
    {
        return $this->evaluate($this->placeholder) ?? __('filament-forms::components.select.placeholder');
    }

    public function getNoOptionsMessage(): string
    {
        return $this->evaluate($this->noOptionsMessage) ?? __('filament-forms::components.select.no_options_message');
    }

    public function getNoSearchResultsMessage(): string
    {
        return $this->evaluate($this->noSearchResultsMessage) ?? __('filament-forms::components.select.no_search_results_message');
    }

    /**
     * Opciones como valor => etiqueta, con las claves en texto.
     *
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        if ($this->cachedOptions !== null) {
            return $this->cachedOptions;
        }

        if ($this->optionModel !== null && $this->options === null) {
            return $this->cachedOptions = $this->getOptionsQuery()
                ->pluck($this->optionTitleAttribute, (new $this->optionModel)->getKeyName())
                ->mapWithKeys(fn (mixed $label, mixed $value): array => [(string) $value => (string) $label])
                ->all();
        }

        $options = $this->evaluate($this->options) ?? [];

        if (is_string($options) && enum_exists($options)) {
            $options = collect($options::cases())->mapWithKeys(fn (BackedEnum $case): array => [
                $case->value => $case instanceof HasLabel ? $case->getLabel() : $case->name,
            ])->all();
        }

        if ($options instanceof Arrayable) {
            $options = $options->toArray();
        }

        return $this->cachedOptions = collect($options)
            ->mapWithKeys(fn (mixed $label, mixed $value): array => [(string) $value => (string) $label])
            ->all();
    }

    /**
     * Fuerza a volver a leer las opciones (después de crear, editar o eliminar).
     */
    public function refreshOptions(): static
    {
        $this->cachedOptions = null;

        return $this;
    }

    /**
     * Opciones listas para Alpine: valor, etiqueta, ícono (HTML) y permisos por opción.
     *
     * @return list<array{value: string, label: string, icon: ?string, canEdit: bool, canDelete: bool}>
     */
    public function getOptionsForJs(): array
    {
        $records = $this->optionModel !== null
            ? $this->getOptionsQuery()->get()->keyBy(fn (Model $record): string => (string) $record->getKey())
            : collect();

        return collect($this->getOptions())
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
                'icon' => $this->getOptionIconHtml($value, $label, $records->get($value)),
                'canEdit' => $this->canEdit($value, $records->get($value)),
                'canDelete' => $this->canDelete($value, $records->get($value)),
            ])
            ->values()
            ->all();
    }

    public function hasOptionMenu(): bool
    {
        return ! $this->isDisabled()
            && ($this->getEditOptionAction() !== null || $this->getDeleteOptionAction() !== null);
    }

    // ------------------------------------------------------------------
    // Acciones
    // ------------------------------------------------------------------

    public function getCreateOptionAction(): ?Action
    {
        if ($this->isDisabled() || ! $this->createOptionForm || ! $this->resolveCreateOptionUsing()) {
            return null;
        }

        return Action::make('createOption')
            ->label(__('Crear'))
            ->icon(Heroicon::Plus)
            ->modalHeading(fn (): string => $this->evaluate($this->createOptionModalHeading) ?? __('Crear opción'))
            ->modalSubmitActionLabel(__('Crear'))
            ->schema(fn (Schema $schema): array|Schema|null => $this->evaluate($this->createOptionForm, [
                'form' => $schema->model($this->optionModel),
                'schema' => $schema->model($this->optionModel),
            ]))
            ->visible(fn (): bool => $this->canCreate())
            ->action(function (array $data, Schema $schema): void {
                abort_unless($this->canCreate(), 403);

                $value = $this->evaluate($this->resolveCreateOptionUsing(), [
                    'data' => $data,
                    'form' => $schema,
                    'schema' => $schema,
                ]);

                $this->refreshOptions();

                // Queda seleccionada: se suma en múltiple y reemplaza en simple.
                $this->state($this->isMultiple()
                    ? array_values(array_unique([...(array) $this->getState(), $value]))
                    : $value);
                $this->callAfterStateUpdated();

                Notification::make()
                    ->success()
                    ->title(__('Opción creada'))
                    ->send();
            });
    }

    public function getEditOptionAction(): ?Action
    {
        if ($this->isDisabled() || ! $this->editOptionForm || ! $this->resolveUpdateOptionUsing()) {
            return null;
        }

        return Action::make('editOption')
            ->label(__('Editar'))
            ->icon(Heroicon::PencilSquare)
            ->modalHeading(fn (array $arguments): string => $this->evaluate($this->editOptionModalHeading, ['value' => $arguments['value'] ?? null])
                ?? __('Editar «:label»', ['label' => $this->getOptions()[(string) ($arguments['value'] ?? '')] ?? '']))
            ->modalSubmitActionLabel(__('Guardar cambios'))
            ->schema(fn (Schema $schema, array $arguments): array|Schema|null => $this->evaluate($this->editOptionForm, [
                'form' => $schema->model($this->findOptionRecord($arguments['value'] ?? null) ?? $this->optionModel),
                'schema' => $schema->model($this->findOptionRecord($arguments['value'] ?? null) ?? $this->optionModel),
                'value' => $arguments['value'] ?? null,
            ]))
            ->fillForm(fn (array $arguments): array => $this->getEditOptionFormData($this->assertOptionValue($arguments)))
            ->action(function (array $data, array $arguments, Schema $schema): void {
                $value = $this->assertOptionValue($arguments);

                abort_unless($this->canEdit($value, $this->findOptionRecord($value)), 403);

                $this->evaluate($this->resolveUpdateOptionUsing(), [
                    'data' => $data,
                    'value' => $value,
                    'form' => $schema,
                    'schema' => $schema,
                ]);

                $this->refreshOptions();

                Notification::make()
                    ->success()
                    ->title(__('Opción actualizada'))
                    ->send();
            });
    }

    public function getDeleteOptionAction(): ?Action
    {
        if ($this->isDisabled() || ! $this->resolveDeleteOptionUsing()) {
            return null;
        }

        return Action::make('deleteOption')
            ->label(__('Eliminar'))
            ->icon(Heroicon::Trash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments): string => __('Eliminar «:label»', [
                'label' => $this->getOptions()[(string) ($arguments['value'] ?? '')] ?? '',
            ]))
            ->modalDescription(fn (array $arguments): string => in_array((string) ($arguments['value'] ?? ''), array_map('strval', (array) $this->getState()), true)
                ? __('Se eliminará el registro de forma permanente y se quitará de la selección actual. Esta acción no se puede deshacer.')
                : __('Se eliminará el registro de forma permanente. Esta acción no se puede deshacer.'))
            ->modalSubmitActionLabel(__('Sí, eliminar'))
            ->action(function (array $arguments): void {
                $value = $this->assertOptionValue($arguments);

                abort_unless($this->canDelete($value, $this->findOptionRecord($value)), 403);

                try {
                    $this->evaluate($this->resolveDeleteOptionUsing(), ['value' => $value]);
                } catch (Halt) {
                    // El modelo ya avisó por qué no se puede eliminar (p. ej. tiene registros relacionados).
                    return;
                } catch (QueryException $exception) {
                    if (! in_array($exception->errorInfo[0] ?? null, ['23000', '23001', '23503'], true)) {
                        throw $exception;
                    }

                    Notification::make()
                        ->danger()
                        ->title(__('No se puede eliminar'))
                        ->body(__('La opción está en uso por otros registros.'))
                        ->send();

                    return;
                }

                $this->refreshOptions();

                // Si estaba seleccionada, sale de la selección.
                $this->state($this->isMultiple()
                    ? array_values(array_filter((array) $this->getState(), fn (mixed $item): bool => (string) $item !== $value))
                    : ((string) $this->getState() === $value ? null : $this->getState()));
                $this->callAfterStateUpdated();

                Notification::make()
                    ->success()
                    ->title(__('Opción eliminada'))
                    ->send();
            });
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    /**
     * @return Builder<Model>
     */
    protected function getOptionsQuery(): Builder
    {
        /** @var Builder<Model> $query */
        $query = $this->optionModel::query()->orderBy($this->optionTitleAttribute);

        if ($this->modifyOptionsQueryUsing) {
            $query = $this->evaluate($this->modifyOptionsQueryUsing, ['query' => $query]) ?? $query;
        }

        return $query;
    }

    protected function findOptionRecord(mixed $value): ?Model
    {
        if ($this->optionModel === null || blank($value)) {
            return null;
        }

        return $this->getOptionsQuery()->whereKey($value)->first();
    }

    /**
     * El valor que llega del navegador debe ser una opción existente.
     *
     * @param  array<string, mixed>  $arguments
     */
    protected function assertOptionValue(array $arguments): string
    {
        $value = (string) ($arguments['value'] ?? '');

        abort_unless(array_key_exists($value, $this->getOptions()), 404);

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getEditOptionFormData(string $value): array
    {
        if ($this->fillEditOptionFormUsing) {
            return $this->evaluate($this->fillEditOptionFormUsing, ['value' => $value]) ?? [];
        }

        return $this->findOptionRecord($value)?->attributesToArray() ?? [];
    }

    protected function resolveCreateOptionUsing(): ?Closure
    {
        if ($this->createOptionUsing || $this->optionModel === null) {
            return $this->createOptionUsing;
        }

        return fn (array $data): string => (string) $this->optionModel::query()->create($data)->getKey();
    }

    protected function resolveUpdateOptionUsing(): ?Closure
    {
        if ($this->updateOptionUsing || $this->optionModel === null) {
            return $this->updateOptionUsing;
        }

        return fn (array $data, string $value): bool => (bool) $this->findOptionRecord($value)?->update($data);
    }

    protected function resolveDeleteOptionUsing(): ?Closure
    {
        if ($this->deleteOptionUsing || $this->optionModel === null) {
            return $this->deleteOptionUsing;
        }

        return fn (string $value): bool => (bool) $this->findOptionRecord($value)?->delete();
    }

    protected function canCreate(): bool
    {
        if ($this->canCreateOption !== null) {
            return (bool) $this->evaluate($this->canCreateOption);
        }

        return $this->optionModel === null || Gate::allows('create', $this->optionModel);
    }

    protected function canEdit(string $value, ?Model $record): bool
    {
        if ($this->isDisabled() || ! $this->editOptionForm || ! $this->resolveUpdateOptionUsing()) {
            return false;
        }

        if ($this->canEditOption !== null) {
            return (bool) $this->evaluate($this->canEditOption, ['value' => $value, 'record' => $record]);
        }

        return $record === null || Gate::allows('update', $record);
    }

    protected function canDelete(string $value, ?Model $record): bool
    {
        if ($this->isDisabled() || ! $this->resolveDeleteOptionUsing()) {
            return false;
        }

        if ($this->canDeleteOption !== null) {
            return (bool) $this->evaluate($this->canDeleteOption, ['value' => $value, 'record' => $record]);
        }

        return $record === null || Gate::allows('delete', $record);
    }

    protected function getOptionIconHtml(string $value, string $label, ?Model $record): ?string
    {
        $icon = match (true) {
            $this->optionIcon instanceof Closure => $this->evaluate($this->optionIcon, ['value' => $value, 'label' => $label, 'record' => $record]),
            is_array($this->optionIcon) => $this->optionIcon[$value] ?? null,
            default => $this->optionIcon,
        };

        if (blank($icon)) {
            return null;
        }

        return generate_icon_html(
            $icon,
            attributes: new ComponentAttributeBag(['class' => 'fi-crud-select-option-icon']),
            size: IconSize::Small,
        )?->toHtml();
    }
}
