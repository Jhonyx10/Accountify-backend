<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template', function (Blueprint $table) {
            $table->id();
            $table->string('template_name', 255);
            $table->text('prompt');
            $table->string('module', 255);
            $table->text('field_json');
            $table->integer('is_tone');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template');
    }
};
