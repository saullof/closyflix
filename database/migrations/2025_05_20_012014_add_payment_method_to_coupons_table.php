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
        Schema::table('coupons', function (Blueprint $table) {
            // Cria a coluna payment_method, tipo string, com valor padrão "all"
            $table->string('payment_method')->default('all')->after('stripe_coupon_id');
            // Você pode ajustar 'after' para colocá-lo na ordem desejada.
        });
    }

    public function down()
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
