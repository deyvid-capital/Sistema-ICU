{{--
    Vista del campo App\Filament\Forms\Components\CrudSelect.

    Usa las mismas clases del tema que el Select de Filament 5 (fi-select-input-*,
    fi-dropdown-*, fi-badge) para verse idéntico en claro y oscuro; lo propio del
    componente (cabecera con «+ Crear», menú ⋮) lleva el prefijo fi-crud-select-* y su
    CSS está en resources/css/filament/admin/components/crud-select.css.

    La lógica vive en resources/js/filament/forms/crud-select.js (se carga con x-load).
--}}
@php
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Support\Facades\Vite;

    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isMultiple = $isMultiple();
    $isSearchable = $isSearchable();
    $options = $getOptionsForJs();
    $canCreate = $getAction('createOption')?->isVisible() ?? false;
    $hasOptionMenu = $hasOptionMenu();
    $placeholder = $getPlaceholder();

    // Si cambian las opciones (crear, editar, eliminar) o el modo, Alpine se reinicia con la lista nueva.
    $renderKey = md5(json_encode([$options, $isDisabled, $isMultiple, $canCreate]));
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <x-filament::input.wrapper
        :disabled="$isDisabled"
        :valid="! $errors->has($statePath)"
        :attributes="
            \Filament\Support\prepare_inherited_attributes($getExtraAttributeBag())
                ->class(['fi-fo-select', 'fi-crud-select'])
        "
    >
        <div
            wire:key="{{ $getLivewireKey() }}.{{ $renderKey }}"
            x-load
            x-load-src="{{ Vite::asset('resources/js/filament/forms/crud-select.js') }}"
            x-data="crudSelectFormComponent({
                state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
                options: @js($options),
                isMultiple: @js($isMultiple),
                isSearchable: @js($isSearchable),
                isClearable: @js($isClearable()),
                isDisabled: @js($isDisabled),
                canCreate: @js($canCreate),
                hasOptionMenu: @js($hasOptionMenu),
                schemaComponent: @js($getKey()),
            })"
            x-on:click.outside="close(false)"
            x-on:focus-input.stop="$refs.trigger?.focus()"
            class="fi-select-input"
        >
            <div
                x-ref="container"
                class="fi-select-input-ctn"
                x-bind:class="{
                    'fi-select-input-ctn-multiple': isMultiple,
                    'fi-select-input-ctn-clearable': canClear,
                }"
            >
                {{-- Disparador: en simple muestra el valor; en múltiple queda detrás de los badges. --}}
                <button
                    type="button"
                    x-ref="trigger"
                    id="{{ $getId() }}"
                    class="fi-select-input-btn"
                    role="combobox"
                    aria-haspopup="listbox"
                    x-bind:aria-expanded="isOpen"
                    @disabled($isDisabled)
                    @class(['fi-disabled' => $isDisabled])
                    x-on:click="toggle()"
                    x-on:keydown="onTriggerKeydown($event)"
                    x-on:keydown.escape.prevent.stop="close()"
                >
                    @unless ($isMultiple)
                        <span class="fi-select-input-value-ctn">
                            <template x-if="hasSelection">
                                <span class="fi-crud-select-value">
                                    <span
                                        x-show="selectedOptions[0]?.icon"
                                        x-html="selectedOptions[0]?.icon"
                                        class="fi-crud-select-value-icon"
                                    ></span>
                                    <span class="fi-select-input-value-label" x-text="selectedOptions[0]?.label"></span>
                                </span>
                            </template>
                            <template x-if="! hasSelection">
                                <span class="fi-select-input-placeholder">{{ $placeholder }}</span>
                            </template>
                        </span>
                    @else
                        <span class="fi-sr-only" x-text="selectedOptions.map((option) => option.label).join(', ')"></span>
                    @endunless
                </button>

                {{-- Múltiple: badges removibles con las opciones elegidas. --}}
                @if ($isMultiple)
                    <div class="fi-select-input-value-ctn">
                        <template x-if="! hasSelection">
                            <span class="fi-select-input-placeholder">{{ $placeholder }}</span>
                        </template>
                        <div x-show="hasSelection" class="fi-select-input-value-badges-ctn">
                            <template x-for="option in selectedOptions" x-bind:key="option.value">
                                <span
                                    class="fi-badge fi-size-md fi-color fi-color-primary fi-text-color-700 dark:fi-text-color-200"
                                    x-bind:data-value="option.value"
                                >
                                    <span x-show="option.icon" x-html="option.icon" class="fi-crud-select-badge-icon"></span>
                                    <span class="fi-badge-label-ctn">
                                        <span class="fi-badge-label" x-text="option.label"></span>
                                    </span>
                                    <button
                                        type="button"
                                        class="fi-badge-delete-btn"
                                        @disabled($isDisabled)
                                        x-bind:aria-label="@js(__('filament-forms::components.select.actions.remove_option.label', ['label' => ''])) + option.label"
                                        x-on:click.stop="remove(option.value)"
                                    >
                                        <span class="fi-badge-delete-btn-icon" aria-hidden="true"></span>
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>
                @endif

                {{-- Limpiar selección (->clearable()). --}}
                <button
                    type="button"
                    x-show="canClear"
                    x-cloak
                    class="fi-select-input-value-remove-btn"
                    aria-label="{{ __('filament-forms::components.select.actions.clear.label') }}"
                    x-on:click.stop="clear()"
                ></button>
            </div>

            {{-- Panel desplegable (posición fija para que no lo recorte un modal ni el scroll). --}}
            <div
                x-show="isOpen"
                x-cloak
                x-anchor.bottom-start.offset.4.fixed="$refs.container"
                x-bind:style="isOpen ? { width: Math.max($refs.container.getBoundingClientRect().width, 256) + 'px' } : {}"
                x-transition:enter="transition ease-out duration-75"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-on:keydown="onPanelKeydown($event)"
                class="fi-dropdown-panel fi-scrollable fi-crud-select-panel"
            >
                {{-- Cabecera: buscador y «+ Crear» en la esquina superior derecha. --}}
                @if ($isSearchable || $canCreate)
                    <div class="fi-crud-select-header">
                        @if ($isSearchable)
                            <div class="fi-select-input-search-ctn fi-crud-select-search">
                                <input
                                    x-ref="search"
                                    x-model.debounce.150ms="search"
                                    type="search"
                                    class="fi-input"
                                    autocomplete="off"
                                    placeholder="{{ __('filament-forms::components.select.search_prompt') }}"
                                    aria-label="{{ __('filament-forms::components.select.search_label') }}"
                                />
                            </div>
                        @else
                            <span class="fi-crud-select-header-spacer"></span>
                        @endif

                        @if ($canCreate)
                            <button type="button" class="fi-crud-select-create-btn" x-on:click.stop="create()">
                                <x-filament::icon :icon="Heroicon::Plus" class="fi-crud-select-create-btn-icon" />
                                <span>{{ __('Crear') }}</span>
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Opciones. --}}
                <ul x-ref="list" role="listbox" @if ($isMultiple) aria-multiselectable="true" @endif class="fi-dropdown-list fi-crud-select-list">
                    <template x-for="(option, index) in filteredOptions" x-bind:key="option.value">
                        <li
                            role="option"
                            class="fi-dropdown-list-item fi-select-input-option fi-crud-select-option"
                            x-bind:class="{
                                'fi-selected': index === highlighted,
                                'fi-crud-select-option-checked': isSelected(option.value),
                            }"
                            x-bind:aria-selected="isSelected(option.value)"
                            x-bind:aria-label="option.label"
                            x-bind:data-highlighted="index === highlighted"
                            x-on:mouseenter="highlighted = index"
                            x-on:click="select(option)"
                        >
                            <span x-show="option.icon" x-html="option.icon" class="fi-crud-select-option-icon-ctn"></span>
                            <span class="fi-dropdown-list-item-label" x-text="option.label"></span>

                            <span x-show="isSelected(option.value)" class="fi-crud-select-check">
                                <x-filament::icon :icon="Heroicon::Check" />
                            </span>

                            <template x-if="hasOptionMenu && (option.canEdit || option.canDelete)">
                                <button
                                    type="button"
                                    class="fi-crud-select-menu-btn"
                                    x-bind:aria-label="@js(__('Opciones de')) + ' ' + option.label"
                                    x-bind:aria-expanded="menuFor === option.value"
                                    x-on:click.stop="toggleMenu(option, $el)"
                                >
                                    <x-filament::icon :icon="Heroicon::EllipsisVertical" />
                                </button>
                            </template>
                        </li>
                    </template>
                </ul>

                <div x-show="filteredOptions.length === 0" class="fi-select-input-message">
                    <span x-show="search.trim() !== ''">{{ $getNoSearchResultsMessage() }}</span>
                    <span x-show="search.trim() === ''">{{ $getNoOptionsMessage() }}</span>
                </div>
            </div>

            {{-- Menú ⋮ de la opción: Editar y Eliminar (cada uno abre su acción con confirmación). --}}
            <div
                x-show="menuFor !== null"
                x-cloak
                x-anchor.bottom-end.offset.4.fixed="menuAnchor"
                x-on:click.outside="closeMenu()"
                class="fi-dropdown-panel fi-crud-select-menu"
                role="menu"
            >
                <div class="fi-dropdown-list">
                    <button
                        type="button"
                        role="menuitem"
                        x-show="menuOption?.canEdit"
                        class="fi-dropdown-list-item"
                        x-on:click.stop="edit()"
                    >
                        <x-filament::icon :icon="Heroicon::PencilSquare" />
                        <span class="fi-dropdown-list-item-label">{{ __('Editar') }}</span>
                    </button>
                    <button
                        type="button"
                        role="menuitem"
                        x-show="menuOption?.canDelete"
                        class="fi-dropdown-list-item fi-color fi-color-danger fi-text-color-600 dark:fi-text-color-400"
                        x-on:click.stop="destroy()"
                    >
                        <x-filament::icon :icon="Heroicon::Trash" />
                        <span class="fi-dropdown-list-item-label">{{ __('Eliminar') }}</span>
                    </button>
                </div>
            </div>
        </div>
    </x-filament::input.wrapper>
</x-dynamic-component>
