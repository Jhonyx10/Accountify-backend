<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('type', 255)->default(0);
            $table->integer('chart_account_id')->default(0);
            $table->string('color', 255)->default('#fc544b');
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_service_categories');
    }
};
