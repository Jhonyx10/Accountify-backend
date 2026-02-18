<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 100);
            $table->string('name', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('card_number', 10)->nullable();
            $table->string('card_exp_month', 10)->nullable();
            $table->string('card_exp_year', 10)->nullable();
            $table->string('plan_name', 100);
            $table->integer('plan_id');
            $table->decimal('price', 15, 2)->default(0.00);
            $table->string('price_currency', 10);
            $table->string('txn_id', 100);
            $table->string('payment_status', 100);
            $table->string('payment_type', 255)->default('Manually');
            $table->string('receipt', 255)->nullable();
            $table->integer('user_id')->default(0);
            $table->integer('is_refund')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
