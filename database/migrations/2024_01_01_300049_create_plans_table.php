<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->decimal('price', 30, 2)->default(0.00);
            $table->string('duration', 100);
            $table->integer('max_users')->default(0);
            $table->integer('max_customers')->default(0);
            $table->integer('max_venders')->default(0);
            $table->string('storage_limit')->default(0);
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->string('enable_chatgpt', 255)->default('off');
            $table->integer('trial')->default(0);
            $table->string('trial_days', 255)->nullable();
            $table->integer('is_disable')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
