<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = ['breezy_sessions', 'passkeys'];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropMorphs('authenticatable');
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->uuidMorphs('authenticatable');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropMorphs('authenticatable');
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->morphs('authenticatable');
            });
        }
    }
};
