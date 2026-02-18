<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bill_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('chart_account_id')->default(0);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->string('description', 255)->nullable();
            $table->string('type', 255);
            $table->integer('ref_id')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bill_accounts');
    }
};
