<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('amount', 16, 2)->default(0.00);
            $table->integer('account_id')->nullable();
            $table->integer('vender_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('category_id')->nullable();
            $table->string('recurring', 255)->nullable();
            $table->integer('payment_method')->nullable();
            $table->string('reference', 255)->nullable();
            $table->string('add_receipt', 255)->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
