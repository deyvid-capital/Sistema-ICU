<?php

use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Las rutas web con `auth` (PDF protegidos, vistas previas) mandan al login del
        // panel: no existe una ruta `login` genérica.
        $middleware->redirectGuestsTo(fn (): string => route('filament.admin.auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Safety net for App\Models\Concerns\PreventsForceDeleteWithDependents:
        // that trait blocks force-deletes with a friendly notification before
        // the query runs, but anything that bypasses Eloquent model events
        // (bulk query-builder deletes, raw SQL) still hits the FK RESTRICT
        // constraint at the database level. Translate that into the same
        // friendly notification instead of leaking a raw SQL error.
        $exceptions->render(function (QueryException $e, Request $request) {
            if (! in_array($e->errorInfo[0] ?? null, ['23001', '23503'], true)) {
                return null;
            }

            $message = 'No se puede eliminar: todavía existen registros relacionados en otro módulo. Elimínalos o reasígnalos primero.';

            Notification::make()
                ->title('No se puede eliminar permanentemente')
                ->body($message)
                ->danger()
                ->send();

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        });
    })->create();
