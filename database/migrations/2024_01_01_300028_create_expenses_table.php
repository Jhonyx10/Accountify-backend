<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id');
            $table->text('description')->nullable();
            $table->decimal('amount', 16, 2)->default(0.00);
            $table->date('date')->nullable();
            $table->unsignedBigInteger('project')->default(0);
            $table->unsignedBigInteger('user_id')->default(0);
            $table->string('attachment', 255)->nullable();
            $table->integer('created_by')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
