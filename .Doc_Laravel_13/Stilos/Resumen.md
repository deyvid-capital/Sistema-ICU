# Ganchos CSS

## Introducción

Filament utiliza clases “gancho” (CSS hooks) para permitir la personalización de varios elementos HTML usando CSS.

## Descubriendo las clases gancho

Podríamos documentar todas las clases gancho presentes en toda la interfaz de Filament, pero eso sería mucho trabajo y probablemente no muy útil para ti. En su lugar, te recomendamos usar las herramientas de desarrollador de tu navegador para inspeccionar los elementos que deseas personalizar y luego usar las clases gancho para apuntar a esos elementos.

Todas las clases gancho llevan el prefijo `fi-`, lo que facilita su identificación. Normalmente, están al inicio de la lista de clases, por lo que son fáciles de encontrar, pero a veces pueden aparecer más abajo si se aplican condicionalmente mediante JavaScript o Blade.

Si no encuentras la clase gancho que buscas, intenta no "hackear" como solución, ya que esto podría exponer tus personalizaciones de estilos a cambios no compatibles en futuras versiones. En vez de eso, abre un pull request para añadir la clase gancho que necesitas. Podemos ayudarte a mantener la coherencia en la nomenclatura. Probablemente, ni siquiera necesites descargar el repositorio de Filament localmente, ya que puedes editar los archivos Blade directamente en GitHub.

## Aplicando estilos a las clases gancho

Por ejemplo, si quieres personalizar el color de la barra lateral (sidebar), puedes inspeccionar ese elemento en las herramientas de desarrollador de tu navegador, ver que usa la clase `fi-sidebar`, y luego añadir CSS en tu app así:

```css
.fi-sidebar {
    background-color: #fafafa;
}
```

Alternativamente, como Filament está construido sobre Tailwind CSS, puedes utilizar la directiva `@apply` para aplicar clases de Tailwind a elementos de Filament:

```css
.fi-sidebar {
    @apply bg-gray-50 dark:bg-gray-950;
}
```

Ocasionalmente, puede que necesites usar el modificador `!important` para sobrescribir estilos existentes, pero úsalo con moderación, ya que puede dificultar el mantenimiento de tus estilos:

```css
.fi-sidebar {
    @apply bg-gray-50 dark:bg-gray-950 !important;
}
```

Incluso puedes aplicar `!important` solo a clases específicas de Tailwind, lo que es menos intrusivo, anteponiendo un `!` al nombre de la clase:

```css
.fi-sidebar {
    @apply !bg-gray-50 dark:!bg-gray-950;
}
```

## Abreviaciones comunes de clases gancho

Usamos algunas abreviaturas comunes en nuestras clases gancho para mantenerlas cortas y legibles:

- `fi` es la abreviatura de “Filament”
- `fi-ac` se usa para clases del package Actions
- `fi-fo` se usa para clases del package Forms
- `fi-in` se usa para clases del package Infolists
- `fi-no` se usa para clases del package Notifications
- `fi-sc` se usa para clases del package Schema
- `fi-ta` se usa para clases del package Tables
- `fi-wi` se usa para clases del package Widgets
- `btn` es la abreviatura de “button” (botón)
- `col` abrevia “column” (columna)
- `ctn` es “container” (contenedor)
- `wrp` es “wrapper” (envoltorio)

## Publicar vistas Blade

Puede que te tiente publicar las vistas internas de Blade en tu aplicación para personalizarlas. No recomendamos hacer esto, ya que podría introducir cambios incompatibles en futuras actualizaciones de tu aplicación. Siempre que sea posible, utiliza las clases gancho CSS.

Si decides publicar las vistas de Blade de todos modos, asegúrate de bloquear todas las versiones de los paquetes de Filament en tu archivo `composer.json`. Luego, actualiza Filament manualmente incrementando el número de versión y prueba toda tu aplicación después de cada actualización. Así podrás identificar de manera segura los cambios incompatibles.