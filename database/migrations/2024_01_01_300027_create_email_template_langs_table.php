<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_template_langs', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id');
            $table->string('lang', 100);
            $table->string('subject', 255);
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_template_langs');
    }
};
