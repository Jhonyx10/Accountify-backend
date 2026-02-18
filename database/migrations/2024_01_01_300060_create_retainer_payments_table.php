<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retainer_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('retainer_id');
            $table->date('date');
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->integer('account_id');
            $table->integer('payment_method');
            $table->string('receipt', 255)->nullable();
            $table->string('payment_type', 255)->default('Manually');
            $table->string('txn_id', 255)->nullable();
            $table->string('currency', 255)->nullable();
            $table->string('order_id', 255)->nullable();
            $table->string('reference', 255)->nullable();
            $table->string('add_receipt', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retainer_payments');
    }
};
