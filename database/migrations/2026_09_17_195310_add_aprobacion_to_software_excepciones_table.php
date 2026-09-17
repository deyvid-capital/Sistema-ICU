<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('software_excepciones', function (Blueprint $table) {
            $table->foreignId('area_aprobadora_id')
                ->nullable()
                ->after('software_id')
                ->constrained('areas', 'id_area')
                ->restrictOnDelete();
            $table->foreignUuid('aprobador_id')
                ->nullable()
                ->after('area_aprobadora_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('estado_aprobacion', 20)
                ->default('pendiente')
                ->after('aprobador_id')
                ->index();
            $table->foreignUuid('decidido_por_id')
                ->nullable()
                ->after('fecha_fin')
                ->constrained('users')
                ->restrictOnDelete();
            $table->timestamp('decidido_at')
                ->nullable()
                ->after('decidido_por_id');
            $table->text('comentario_decision')
                ->nullable()
                ->after('decidido_at');
        });
    }

    public function down(): void
    {
        Schema::table('software_excepciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_aprobadora_id');
            $table->dropConstrainedForeignId('aprobador_id');
            $table->dropConstrainedForeignId('decidido_por_id');
            $table->dropColumn([
                'estado_aprobacion',
                'decidido_at',
                'comentario_decision',
            ]);
        });
    }
};
