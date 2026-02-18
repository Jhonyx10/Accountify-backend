<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id');
            $table->integer('plan_id');
            $table->decimal('plan_price', 15, 2)->default(0.00);
            $table->integer('commission');
            $table->integer('referral_code')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_transactions');
    }
};
