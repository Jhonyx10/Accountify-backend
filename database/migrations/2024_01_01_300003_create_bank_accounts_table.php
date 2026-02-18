<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('holder_name', 255);
            $table->string('bank_name', 255);
            $table->string('account_number', 255);
            $table->integer('chart_account_id')->default(0);
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->string('contact_number', 255)->nullable();
            $table->text('bank_address')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
