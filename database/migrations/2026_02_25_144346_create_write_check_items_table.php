<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('write_check_items', function (Blueprint $table) {
            $table->id();
            $table->integer('write_check_id');
            $table->integer('chart_of_account_id')->default(0);
            $table->integer('product_id')->default(0);
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('write_check_items');
    }
};
