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
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->change();
            $table->string('used_referral_code')->nullable()->change();
        });

        Schema::table('referral_transactions', function (Blueprint $table) {
            $table->string('referral_code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('referral_code')->default(0)->change();
            $table->integer('used_referral_code')->default(0)->change();
        });

        Schema::table('referral_transactions', function (Blueprint $table) {
            $table->integer('referral_code')->default(0)->change();
        });
    }
};
