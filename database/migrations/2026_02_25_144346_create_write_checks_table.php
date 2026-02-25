<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('write_checks', function (Blueprint $table) {
            $table->id();
            $table->integer('bank_account_id');
            $table->integer('payee_id')->default(0);
            $table->integer('payee_type')->default(0); // 1 = Vendor, 2 = Customer
            $table->date('date');
            $table->string('reference', 255)->nullable();
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('write_checks');
    }
};
