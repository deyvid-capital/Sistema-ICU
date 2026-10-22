<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * spatie/laravel-activitylog v5 guarda el diff de atributos (`old` y
 * `attributes`) en la columna propia `attribute_changes` y deja `properties`
 * solo para datos personalizados. También elimina el sistema de lotes, así que
 * `batch_uuid` ya no se usa. Se mueve el histórico para que el panel y la
 * exportación CSV lo sigan mostrando.
 */
return new class extends Migration
{
    private const CLAVES_DEL_DIFF = ['old', 'attributes'];

    public function up(): void
    {
        Schema::table('activity_log', function (Blueprint $table): void {
            $table->json('attribute_changes')->nullable()->after('causer_id');
            $table->dropColumn('batch_uuid');
        });

        DB::table('activity_log')
            ->where(function ($query): void {
                $query->whereNotNull('properties->attributes')
                    ->orWhereNotNull('properties->old');
            })
            ->chunkById(500, function ($registros): void {
                foreach ($registros as $registro) {
                    $propiedades = json_decode((string) $registro->properties, true) ?? [];
                    $cambios = array_intersect_key($propiedades, array_flip(self::CLAVES_DEL_DIFF));
                    $restantes = array_diff_key($propiedades, array_flip(self::CLAVES_DEL_DIFF));

                    DB::table('activity_log')->where('id', $registro->id)->update([
                        'attribute_changes' => $cambios === [] ? null : json_encode($cambios),
                        'properties' => $restantes === [] ? null : json_encode($restantes),
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('activity_log')
            ->whereNotNull('attribute_changes')
            ->chunkById(500, function ($registros): void {
                foreach ($registros as $registro) {
                    $propiedades = json_decode((string) $registro->properties, true) ?? [];
                    $cambios = json_decode((string) $registro->attribute_changes, true) ?? [];

                    DB::table('activity_log')->where('id', $registro->id)->update([
                        'properties' => json_encode([...$propiedades, ...$cambios]),
                    ]);
                }
            });

        Schema::table('activity_log', function (Blueprint $table): void {
            $table->dropColumn('attribute_changes');
            $table->uuid('batch_uuid')->nullable();
        });
    }
};
