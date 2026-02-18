<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('invoice_id');
            $table->integer('retainer_id');
            $table->integer('order_id');
            $table->string('amount', 255);
            $table->string('status', 255);
            $table->string('receipt', 255)->nullable();
            $table->string('type', 255);
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfers');
    }
};
