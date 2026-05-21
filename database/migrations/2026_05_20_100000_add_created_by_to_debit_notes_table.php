<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('debit_notes', 'created_by')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                $table->integer('created_by')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('debit_notes', 'created_by')) {
            Schema::table('debit_notes', function (Blueprint $table) {
                $table->dropColumn('created_by');
            });
        }
    }
};
