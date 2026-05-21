<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the column exists before trying to add it
        if (!Schema::hasColumn('roles', 'created_by')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->default(0)->after('guard_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('roles', 'created_by')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('created_by');
            });
        }
    }
};
