<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('transfers', 'status')) {
                $table->string('status', 50)->default('Completed')->after('description');
            }
            if (!Schema::hasColumn('transfers', 'journal_entry_id')) {
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transfers', function (Blueprint $table) {
            $table->dropColumnIfExists('status');
            $table->dropColumnIfExists('journal_entry_id');
        });
    }
};
