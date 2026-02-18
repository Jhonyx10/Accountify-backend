<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_products', function (Blueprint $table) {
            $table->id();
            $table->integer('bill_id');
            $table->integer('product_id');
            $table->decimal('quantity', 15, 2)->default(0.00);
            $table->string('tax', 50)->nullable();
            $table->decimal('discount', 15, 2)->default(0.00);
            $table->decimal('price', 16, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_products');
    }
};
