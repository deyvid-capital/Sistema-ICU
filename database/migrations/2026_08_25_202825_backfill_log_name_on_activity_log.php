<?php

use App\Enums\DominioActividad;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reclasifica los registros de actividad ya existentes: hasta ahora todos se
 * guardaban con el `log_name` genérico de Spatie ('default'), así que el filtro
 * "Tipo" del panel solo ofrecía una opción. Se deriva el dominio desde
 * `subject_type` para que el histórico quede alineado con los nuevos registros.
 */
return new class extends Migration
{
    public function up(): void
    {
        $generico = (string) config('activitylog.default_log_name');

        foreach (DominioActividad::mapaDeModelos() as $modelo => $dominio) {
            DB::table('activity_log')
                ->where('subject_type', $modelo)
                ->where(function ($query) use ($generico): void {
                    $query->where('log_name', $generico)
                        ->orWhereNull('log_name');
                })
                ->update(['log_name' => $dominio->value]);
        }
    }

    public function down(): void
    {
        $dominios = array_map(
            fn (DominioActividad $dominio): string => $dominio->value,
            DominioActividad::cases(),
        );

        DB::table('activity_log')
            ->whereIn('log_name', $dominios)
            ->update(['log_name' => (string) config('activitylog.default_log_name')]);
    }
};
