<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venders', function (Blueprint $table) {
            $table->id();
            $table->integer('vender_id');
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('tax_number', 255)->nullable();
            $table->string('password', 255);
            $table->string('contact', 255)->nullable();
            $table->string('avatar', 100);
            $table->integer('created_by')->default(0);
            $table->integer('is_active')->default(1);
            $table->integer('is_enable_login')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('billing_name', 255)->nullable();
            $table->string('billing_country', 255)->nullable();
            $table->string('billing_state', 255)->nullable();
            $table->string('billing_city', 255)->nullable();
            $table->string('billing_phone', 255)->nullable();
            $table->string('billing_zip', 255)->nullable();
            $table->text('billing_address')->nullable();
            $table->string('shipping_name', 255)->nullable();
            $table->string('shipping_country', 255)->nullable();
            $table->string('shipping_state', 255)->nullable();
            $table->string('shipping_city', 255)->nullable();
            $table->string('shipping_phone', 255)->nullable();
            $table->string('shipping_zip', 255)->nullable();
            $table->string('shipping_address', 255)->nullable();
            $table->string('lang', 255)->default('en');
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('remember_token', 100)->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venders');
    }
};
