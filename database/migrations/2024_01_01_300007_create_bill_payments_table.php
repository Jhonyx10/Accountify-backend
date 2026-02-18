<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('bill_id');
            $table->date('date')->nullable();
            $table->decimal('amount', 16, 2)->default(0.00);
            $table->integer('account_id')->nullable();
            $table->integer('payment_method')->nullable();
            $table->string('reference', 255)->nullable();
            $table->string('add_receipt', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
