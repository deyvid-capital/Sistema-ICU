---
name: trampas-tests-y-listeners
description: Dos trampas del proyecto que rompen tests de autorización y duplican listeners de eventos
metadata:
  type: project
---

Dos comportamientos no obvios descubiertos el 2026-09-10 al implementar la auditoría de accesos.

## 1. No registrar listeners a mano en AppServiceProvider

Laravel 11+ **autodescubre** los listeners de `app/Listeners` por el tipo del argumento de `handle()`. Añadir además un `Event::listen(...)` en `AppServiceProvider::boot()` los registra **dos veces** y el listener se ejecuta por duplicado.

**Por qué importa:** pasó desapercibido años con `ResetearIntentosAlIniciarSesion` (es idempotente), pero cualquier listener que escriba en BD genera filas duplicadas. Se detectó porque cada login escribía dos registros de auditoría.

**Cómo aplicarlo:** basta con crear la clase en `app/Listeners` con `handle(MiEvento $event)`. Verificar con `Event::getRawListeners()`: si aparece la clase dos veces (una a secas y otra con `@handle`), está duplicada.

## 2. `skipAuthorization()` de Filament es global, no por recurso

`$shouldSkipAuthorization` se declara en el trait `HasAuthorization` que usa `Filament\Resources\Resource`. Las subclases no lo redeclaran, así que **comparten el mismo almacenamiento**: `EquipoResource::skipAuthorization()` desactiva la autorización de **todos** los recursos durante el resto del proceso PHP.

**Por qué importa:** ~15 clases de `tests/Feature/Filament` lo llaman en `setUp` y nunca lo reponen. Cualquier test posterior que afirme un `assertForbidden()` recibirá 200 al correr la suite completa, aunque pase en aislamiento.

**Cómo aplicarlo:** en un test que compruebe denegación de permisos, poner `MiResource::skipAuthorization(false);` en el `setUp`, y añadir `app(PermissionRegistrar::class)->forgetCachedPermissions()`, porque `CACHE_STORE=array` sobrevive a `RefreshDatabase`.

## 3. El filtro de eventos del paquete rmsramos/activitylog no descarta nulos

`ActivitylogResource::getEventFilterComponent()` del vendor hace `distinct()->pluck('event','event')` sin `->filter()`, así que una fila con `event` nulo genera una opción de clave vacía etiquetada con la clave cruda `activitylog::action.event.`. Está sobrescrito en `app/Filament/Resources/Activitylogs/ActivitylogResource.php` usando el vocabulario cerrado (eventos de modelo + `EventoSeguridad`) agrupado. Lo mismo vale para cualquier `__()` sobre un estado que pueda venir nulo: hay que comprobar `trans()->has()` antes.

Relacionado: [[suite-completa-verde]], [[control-acceso-seguridad-usuarios]]

**Modelo nuevo con `LogsIcuActivity`:** hay que agregarlo a `DominioActividad::mapaDeModelos()`; si no, falla `tests/Unit/Enums/DominioActividadTest` (solo se ve en la suite completa). Pasó con `GuiaEdicion` el 2026-11-11 (quedó en Documentos).
