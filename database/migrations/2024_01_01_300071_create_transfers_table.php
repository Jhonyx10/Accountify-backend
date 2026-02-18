<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->integer('from_account')->default(0);
            $table->integer('to_account')->default(0);
            $table->decimal('amount', 16, 2)->default(0.00);
            $table->date('date');
            $table->integer('payment_method')->default(0);
            $table->string('reference', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
