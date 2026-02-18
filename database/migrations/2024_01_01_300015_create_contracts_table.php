<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->integer('customer')->default(0);
            $table->string('subject', 255)->nullable();
            $table->decimal('value', 15, 2)->default(0.00);
            $table->integer('type')->default(0);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('edit_status', 255)->default('pending');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->text('customer_signature')->nullable();
            $table->text('company_signature')->nullable();
            $table->integer('created_by');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
