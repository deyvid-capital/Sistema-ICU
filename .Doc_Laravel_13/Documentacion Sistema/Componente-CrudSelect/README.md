# CrudSelect: select con crear, editar y eliminar opciones

Campo de formulario propio para Filament 5. Se ve y se comporta como el `Select`
de Filament (selección única o múltiple, búsqueda, limpiar, validación, `->live()`),
y además permite **gestionar las opciones desde el mismo desplegable**:

- **«+ Crear»** en la esquina superior derecha del desplegable. Abre un modal, guarda
  el registro y lo deja seleccionado.
- **Menú ⋮ en cada opción** con **Editar** (modal con el formulario) y **Eliminar**
  (con confirmación explícita).
- **Ícono por opción**, visible en la lista, en el valor elegido y en los badges.

No extiende, no envuelve y no delega en `Filament\Forms\Components\Select`.

---

## 1. Archivos y dónde va cada uno

| Archivo | Qué es | Por qué ahí |
|---|---|---|
| `app/Filament/Forms/Components/CrudSelect.php` | La clase del campo (extiende `Filament\Forms\Components\Field`) | Es donde la deja `php artisan make:filament-form-field` en Filament 5 |
| `resources/views/filament/forms/components/crud-select.blade.php` | La vista (Blade + Alpine) | Ruta que genera el mismo comando; queda junto a las demás vistas de Filament del proyecto |
| `resources/js/filament/forms/crud-select.js` | La lógica de Alpine (abrir, buscar, teclado, selección, menú ⋮) | Igual que `resources/js/firma/canvas-firmas.js`: se compila con Vite y se carga bajo demanda con `x-load` solo donde hay un CrudSelect |
| `resources/css/filament/admin/components/crud-select.css` | Estilos propios (cabecera, ⋮, check, panel amplio) | Lo importa el tema del panel (`resources/css/filament/admin/theme.css`) |
| `tests/Feature/Filament/CrudSelectTest.php` y `tests/Feature/Filament/Fixtures/CrudSelectFormFixture.php` | Tests y un formulario mínimo para probarlo | Convención de tests del proyecto |

## 2. Instalación y registro

No hay proveedor de servicios ni registro manual:

1. La clase se usa directamente: `use App\Filament\Forms\Components\CrudSelect;`.
2. El JS está declarado como entrada en `vite.config.js`, y el CSS se importa en
   `resources/css/filament/admin/theme.css`.
3. Después de cambiar el JS o el CSS hay que compilar:

```bash
vendor/bin/sail pnpm run build   # o: vendor/bin/sail pnpm run dev
```

Si el desplegable no responde o se ve sin estilos, casi siempre falta compilar.

## 3. Uso

### Selección única

```php
use App\Filament\Forms\Components\CrudSelect;

CrudSelect::make('prioridad')
    ->label('Prioridad')
    ->options([
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
    ])
    ->placeholder('Elige una prioridad')
    ->required()
    ->helperText('Define el orden de atención.');
```

### Selección múltiple

```php
CrudSelect::make('etiquetas')
    ->multiple()
    ->options(Etiqueta::query()->pluck('nombre', 'id'))
    ->searchable()   // ya viene activado por defecto
    ->clearable();   // botón ✕ para limpiar todo
```

En múltiple el estado es un arreglo de claves, por ejemplo `['3', '7']`. Guárdalo en
una columna JSON (con cast `array`) o procésalo tú, por ejemplo con
`->saveRelationshipsUsing()` para una relación `BelongsToMany`.

### Con un modelo Eloquent y CRUD completo

`optionsFromModel()` toma las opciones del modelo y deja listos **Crear, Editar y
Eliminar** contra él. Solo falta declarar los formularios:

```php
use App\Models\CategoriaSoftware;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;

$formulario = [
    TextInput::make('nombre')->required()->maxLength(100),
    TextInput::make('codigo')->required()->maxLength(50),
];

CrudSelect::make('categoria_id')
    ->label('Categoría')
    ->optionsFromModel(CategoriaSoftware::class, 'nombre')
    ->optionIcon(Heroicon::OutlinedTag)
    ->createOptionForm($formulario)
    ->editOptionForm($formulario)
    ->required();
```

- **Crear:** hace `Model::create($data)` y selecciona la clave nueva. En simple
  reemplaza el valor; en múltiple lo suma.
- **Editar:** llena el formulario con `attributesToArray()` del registro y hace
  `update($data)`.
- **Eliminar:** pide confirmación y hace `delete()`. Si la opción estaba
  seleccionada, sale de la selección.
- **Permisos:** por defecto salen de la **policy del modelo** (`create`, `update` y
  `delete`); en este proyecto son los permisos de Shield. Las acciones se vuelven a
  autorizar en el servidor, así que ocultar un botón no es la única barrera.

Para filtrar u ordenar las opciones, pásale una consulta:

```php
->optionsFromModel(
    SitioSharepoint::class,
    'nombre_sitio',
    fn ($query) => $query->where('estado', true),
)
```

### Callbacks a mano (sin modelo, o para cambiar el comportamiento)

Todo lo que declares a mano tiene prioridad sobre lo que hace `optionsFromModel()`:

```php
CrudSelect::make('fondo_id')
    ->options(fn () => Fondo::query()->pluck('codigo', 'id_fondo'))

    // + Crear
    ->createOptionForm([TextInput::make('codigo')->required()])
    ->createOptionUsing(fn (array $data): string => (string) Fondo::create($data)->getKey())
    ->createOptionModalHeading('Nuevo fondo')

    // ⋮ Editar
    ->editOptionForm([TextInput::make('codigo')->required()])
    ->fillEditOptionFormUsing(fn (string $value): array => Fondo::findOrFail($value)->only('codigo'))
    ->updateOptionUsing(fn (array $data, string $value) => Fondo::findOrFail($value)->update($data))

    // ⋮ Eliminar (la confirmación es automática)
    ->deleteOptionUsing(fn (string $value) => Fondo::findOrFail($value)->delete())

    // Permisos por opción
    ->canCreateOption(fn (): bool => auth()->user()->can('Create:Fondo'))
    ->canEditOption(fn (string $value): bool => auth()->user()->can('Update:Fondo'))
    ->canDeleteOption(fn (string $value): bool => $value !== '1');
```

Cada acción aparece solo si tiene todo lo que necesita:

| Acción | Requiere |
|---|---|
| Crear | `createOptionForm` + (`createOptionUsing` u `optionsFromModel`) |
| Editar | `editOptionForm` + (`updateOptionUsing` u `optionsFromModel`) |
| Eliminar | `deleteOptionUsing` u `optionsFromModel` |

Si el campo está `->disabled()`, no se muestra ninguna.

### Íconos

```php
->optionIcon(Heroicon::OutlinedTag)                       // el mismo para todas

->optionIcon([                                            // por valor
    'pdf' => Heroicon::OutlinedDocument,
    'vpn' => Heroicon::OutlinedLockClosed,
])

->optionIcon(fn (string $value, string $label, ?Model $record) => $record?->estado
    ? Heroicon::OutlinedCheckCircle
    : Heroicon::OutlinedXCircle)                          // calculado (con $record si usas optionsFromModel)
```

Acepta cualquier ícono de Filament: el enum `Heroicon` o un nombre de Blade Icons.

### Resumen de opciones

| Método | Para qué |
|---|---|
| `options(array \| Closure \| Enum::class)` | Opciones `valor => etiqueta` (también un enum con `HasLabel`) |
| `optionsFromModel(Model::class, 'atributo', ?Closure)` | Opciones y CRUD contra un modelo |
| `multiple()` | Selección múltiple con badges |
| `searchable(bool)` | Buscador (activo por defecto; ignora mayúsculas y tildes) |
| `clearable(bool)` | Botón ✕ (por defecto, solo si el campo no es `required`) |
| `placeholder()`, `noOptionsMessage()`, `noSearchResultsMessage()` | Textos |
| `optionIcon()` | Ícono por opción |
| `required()`, `disabled()`, `helperText()`, `hint()`, `live()`, `afterStateUpdated()` | Los de cualquier `Field` de Filament |

**Validación:** además de las reglas que agregues, el campo solo acepta valores que
estén entre sus opciones, igual que el Select nativo. En múltiple exige un arreglo.

**Teclado:** `↓`/`↑` mueven la opción resaltada, `Enter` selecciona, `Escape` cierra
el menú ⋮ o el desplegable (sin cerrar el modal que lo contiene) y `Tab` cierra. Con
el desplegable cerrado, `↓`, `↑`, `Enter` o `Espacio` lo abren.

## 4. Notas de arquitectura

### Por qué es un `Field` independiente

El Select de Filament 5 arma su desplegable en JavaScript (`select.js` de
`filament/support`), y ese código no tiene un lugar para meter un botón por opción
ni una cabecera con acciones. Extenderlo obligaría a depender de sus detalles
internos, que cambian entre versiones. Por eso `CrudSelect` extiende
`Filament\Forms\Components\Field` y tiene su propia vista y su propio Alpine.

La **paridad visual** se logra usando las **mismas clases del tema de Filament**
(`fi-select-input`, `fi-select-input-btn`, `fi-dropdown-panel`, `fi-dropdown-list-item`,
`fi-badge`…) dentro del envoltorio oficial `<x-filament::input.wrapper>`, que es el
que da el borde, el foco, el estado de error, el deshabilitado y el modo oscuro. Si
Filament cambia esos estilos, el componente los hereda.

### Cómo viaja el estado entre Alpine y Livewire

```
Servidor (PHP)                         Navegador (Alpine)
──────────────                         ──────────────────
getOptionsForJs() ── JSON ──────────►  options   (lista para pintar)
statePath ◄────── $wire.$entangle ──►  state     (clave o arreglo de claves)
```

- `state` está enlazado con `$wire.$entangle()`. Si el campo es `->live()`, el cambio
  viaja al servidor en el acto; si no, viaja con el siguiente envío.
- Las opciones las calcula el servidor (texto, ícono como HTML, y si cada una se
  puede editar o eliminar). La vista tiene un `wire:key` con un hash de las opciones:
  después de crear, editar o eliminar, el hash cambia y Alpine se vuelve a iniciar
  con la lista nueva. La selección no se pierde, porque vive en Livewire.
- **Crear, Editar y Eliminar** son **acciones de Filament registradas en el campo**
  (`createOption`, `editOption`, `deleteOption`). El JS solo las abre con
  `$wire.mountAction(nombre, { value }, { schemaComponent })`, el mismo mecanismo que
  usan el Builder y el Rich Editor de Filament. El modal, la validación del formulario,
  la confirmación, las notificaciones y los permisos los resuelve el servidor.
- El valor que llega del navegador se comprueba: debe ser una opción existente (si no,
  responde 404) y el usuario debe tener permiso (si no, 403).
- **Eliminar algo en uso:** si el modelo detiene el borrado (en ICU,
  `PreventsForceDeleteWithDependents` muestra su propio aviso) o la base rechaza el
  borrado por llave foránea, el componente avisa «No se puede eliminar» y la selección
  queda intacta.

### Posicionamiento

El desplegable y el menú ⋮ usan `x-anchor` (incluido en el Alpine de Livewire) con
posición **fija**, así que no los recorta el scroll ni el borde de un modal.

## 5. Tests

```bash
vendor/bin/sail artisan test --compact tests/Feature/Filament/CrudSelectTest.php
```

Cubren:
- el renderizado con las clases de Filament y el ícono;
- la selección única y la múltiple;
- el rechazo de valores fuera de las opciones y el `required`;
- crear (selecciona en simple y suma en múltiple) y la validación de su formulario;
- editar;
- eliminar (con confirmación y quitando de la selección);
- no eliminar una opción en uso;
- los permisos (403), los valores inventados (404) y la policy del modelo.

Para probar acciones del campo en un test:

```php
Livewire::test(MiPagina::class)
    ->callAction(TestAction::make('createOption')->schemaComponent('categoria'), data: [...])
    ->mountAction(TestAction::make('editOption')->schemaComponent('categoria')->arguments(['value' => '5']));
```

`schemaComponent()` recibe la clave del campo **sin** el `statePath` del formulario
(`'categoria'`, no `'data.categoria'`).
