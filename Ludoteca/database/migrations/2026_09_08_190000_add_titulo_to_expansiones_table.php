<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('expansiones', 'titulo')) {
            Schema::table('expansiones', function (Blueprint $table) {
                $table->string('titulo', 100)->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('expansiones', 'titulo')) {
            Schema::table('expansiones', function (Blueprint $table) {
                $table->dropColumn('titulo');
            });
        }
    }
};
