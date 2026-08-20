<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo_evento', 100);
            $table->string('canal', 20);
            $table->boolean('habilitado')->default(true);
            $table->timestamps();

            $table->unique(['usuario_id', 'tipo_evento', 'canal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
