/**
 * CrudSelect: lógica de Alpine del campo App\Filament\Forms\Components\CrudSelect.
 *
 * Se carga bajo demanda con `x-load` desde la vista
 * resources/views/filament/forms/components/crud-select.blade.php.
 *
 * Estado: `state` está enlazado con Livewire mediante `$wire.$entangle()` (con `.live`
 * si el campo es `->live()`), así que cada cambio aquí llega al servidor y al revés.
 * Las opciones las arma el servidor; cuando cambian (crear, editar, eliminar) la vista
 * cambia de `wire:key` y Alpine se vuelve a iniciar con la lista nueva.
 *
 * Crear, Editar y Eliminar son acciones de Filament del propio campo: aquí solo se abren
 * con `$wire.mountAction(nombre, argumentos, { schemaComponent })`; el modal, la
 * validación, la confirmación y los permisos los resuelve el servidor.
 */

/** Texto comparable: minúsculas y sin tildes, para que «edicion» encuentre «Edición». */
function normalizar(texto) {
    return String(texto ?? '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim()
}

export default function crudSelectFormComponent({
    state,
    options,
    isMultiple,
    isSearchable,
    isClearable,
    isDisabled,
    canCreate,
    hasOptionMenu,
    schemaComponent,
}) {
    return {
        state,
        options,
        isOpen: false,
        search: '',
        highlighted: -1,
        menuFor: null,
        menuAnchor: null,

        init() {
            // Normaliza el estado inicial: lista en múltiple, escalar o null en simple.
            if (isMultiple && !Array.isArray(this.state)) {
                this.state = this.state === null || this.state === undefined || this.state === '' ? [] : [this.state]
            }

            this.$watch('search', () => {
                this.highlighted = this.filteredOptions.length ? 0 : -1
            })
        },

        // ------------------------------------------------------------------
        // Lectura
        // ------------------------------------------------------------------

        get filteredOptions() {
            if (!isSearchable || this.search.trim() === '') {
                return this.options
            }

            const termino = normalizar(this.search)

            return this.options.filter((option) => normalizar(option.label).includes(termino))
        },

        get selectedValues() {
            if (isMultiple) {
                return (Array.isArray(this.state) ? this.state : []).map(String)
            }

            return this.state === null || this.state === undefined || this.state === '' ? [] : [String(this.state)]
        },

        /** Opciones seleccionadas en el orden en que se eligieron. */
        get selectedOptions() {
            return this.selectedValues.map(
                (value) =>
                    this.options.find((option) => option.value === value) ?? {
                        value,
                        label: value,
                        icon: null,
                    },
            )
        },

        get hasSelection() {
            return this.selectedValues.length > 0
        },

        get canClear() {
            return isClearable && !isDisabled && this.hasSelection
        },

        isSelected(value) {
            return this.selectedValues.includes(String(value))
        },

        // ------------------------------------------------------------------
        // Abrir y cerrar
        // ------------------------------------------------------------------

        toggle() {
            this.isOpen ? this.close() : this.open()
        },

        open() {
            if (isDisabled || this.isOpen) {
                return
            }

            this.isOpen = true
            this.menuFor = null

            // Resalta la opción elegida (o la primera) y lleva el foco al buscador.
            const elegida = this.filteredOptions.findIndex((option) => this.isSelected(option.value))
            this.highlighted = elegida >= 0 ? elegida : this.filteredOptions.length ? 0 : -1

            this.$nextTick(() => {
                if (isSearchable) {
                    this.$refs.search?.focus()
                }

                this.scrollToHighlighted()
            })
        },

        close(devolverFoco = true) {
            if (!this.isOpen) {
                return
            }

            this.isOpen = false
            this.search = ''
            this.closeMenu()

            if (devolverFoco) {
                this.$nextTick(() => this.$refs.trigger?.focus())
            }
        },

        // ------------------------------------------------------------------
        // Selección
        // ------------------------------------------------------------------

        select(option) {
            if (isDisabled || !option) {
                return
            }

            if (!isMultiple) {
                this.state = option.value
                this.close()

                return
            }

            // Se asigna un arreglo nuevo para que $entangle detecte el cambio.
            this.state = this.isSelected(option.value)
                ? this.selectedValues.filter((value) => value !== option.value)
                : [...this.selectedValues, option.value]
        },

        remove(value) {
            if (isDisabled) {
                return
            }

            this.state = isMultiple ? this.selectedValues.filter((item) => item !== String(value)) : null
        },

        clear() {
            if (!this.canClear) {
                return
            }

            this.state = isMultiple ? [] : null
            this.$refs.trigger?.focus()
        },

        // ------------------------------------------------------------------
        // Teclado: ArrowUp, ArrowDown, Enter, Escape (y Tab cierra)
        // ------------------------------------------------------------------

        onTriggerKeydown(event) {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key) && !this.isOpen) {
                event.preventDefault()
                this.open()
            }
        },

        onPanelKeydown(event) {
            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault()
                    this.move(1)
                    break
                case 'ArrowUp':
                    event.preventDefault()
                    this.move(-1)
                    break
                case 'Enter':
                    event.preventDefault()
                    this.select(this.filteredOptions[this.highlighted])
                    break
                case 'Escape':
                    event.preventDefault()
                    event.stopPropagation() // No cierra el modal que contiene al campo.
                    this.menuFor !== null ? this.closeMenu() : this.close()
                    break
                case 'Tab':
                    this.close(false)
                    break
            }
        },

        move(paso) {
            const total = this.filteredOptions.length

            if (!total) {
                this.highlighted = -1

                return
            }

            this.closeMenu()
            this.highlighted = (this.highlighted + paso + total) % total
            this.scrollToHighlighted()
        },

        scrollToHighlighted() {
            this.$nextTick(() => {
                this.$refs.list?.querySelector('[data-highlighted="true"]')?.scrollIntoView({ block: 'nearest' })
            })
        },

        // ------------------------------------------------------------------
        // Menú ⋮ y acciones CRUD
        // ------------------------------------------------------------------

        toggleMenu(option, boton) {
            if (this.menuFor === option.value) {
                this.closeMenu()

                return
            }

            this.menuFor = option.value
            this.menuAnchor = boton
        },

        closeMenu() {
            this.menuFor = null
            this.menuAnchor = null
        },

        get menuOption() {
            return this.options.find((option) => option.value === this.menuFor) ?? null
        },

        create() {
            if (!canCreate) {
                return
            }

            this.mount('createOption')
        },

        edit() {
            const option = this.menuOption

            if (option?.canEdit) {
                this.mount('editOption', { value: option.value })
            }
        },

        destroy() {
            const option = this.menuOption

            if (option?.canDelete) {
                this.mount('deleteOption', { value: option.value })
            }
        },

        /** Cierra el desplegable y abre la acción de Filament del campo. */
        mount(nombre, argumentos = {}) {
            this.close(false)
            this.$wire.mountAction(nombre, argumentos, { schemaComponent })
        },

        hasOptionMenu,
        canCreate,
        isMultiple,
        isSearchable,
        isDisabled,
    }
}
