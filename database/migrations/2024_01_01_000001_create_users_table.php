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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('type', 100);
            $table->string('avatar', 100)->nullable();
            $table->string('lang', 100)->default('en');
            $table->string('mode', 10)->default('light');
            $table->integer('created_by')->default(0);
            $table->integer('plan')->nullable();
            $table->date('plan_expire_date')->nullable();
            $table->integer('requested_plan')->default(0);
            $table->integer('referral_code')->default(0);
            $table->integer('used_referral_code')->default(0);
            $table->integer('is_active')->default(1);
            $table->integer('is_enable_login')->default(1);
            $table->integer('is_trial_done')->default(0);
            $table->integer('is_plan_purchased')->default(0);
            $table->integer('is_register_trial')->default(0);
            $table->string('interested_plan_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

