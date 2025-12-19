<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('stripe_coupon_id')->nullable();
            $table->string('coupon_code')->nullable();
            // Permite null pois, se o desconto for fixo, usaremos amount_off
            $table->decimal('discount_percent', 5, 2)->nullable();
            // Novo campo para definir o tipo de desconto
            $table->enum('discount_type', ['percent', 'fixed'])->default('percent');
            // Novo campo para desconto fixo (em centavos)
            $table->integer('amount_off')->nullable();
            $table->enum('expiration_type', ['never', 'usage', 'date']);
            $table->integer('usage_limit')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->integer('times_used')->default(0);
            $table->foreignId('creator_id')->constrained('users');
            $table->timestamps();
            $table->string('stripe_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            // Definindo um status default para evitar problemas
            $table->string('status')->default('active');
            $table->integer('usage_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('coupons');
    }
};
