<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('noxpay_payment_code')->nullable()->after('suitpay_payment_transaction_id');
                $table->string('noxpay_payment_txid')->nullable()->after('noxpay_payment_code');
                $table->text('noxpay_qr_code')->nullable()->after('noxpay_payment_txid');
                $table->text('noxpay_qr_code_text')->nullable()->after('noxpay_qr_code');
                $table->string('noxpay_payment_url')->nullable()->after('noxpay_qr_code_text');
            });
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->insert([
                [
                    'key' => 'payments.noxpay_api_key',
                    'display_name' => 'NoxPay API Key',
                    'value' => null,
                    'details' => null,
                    'type' => 'text',
                    'order' => 52,
                    'group' => 'Payments',
                ],
                [
                    'key' => 'payments.noxpay_checkout_disabled',
                    'display_name' => 'Disable for checkout',
                    'value' => 0,
                    'details' => '{
                        "true" : "On",
                        "false" : "Off",
                        "checked" : false,
                        "description": "Won`t be shown on checkout, but it`s still available for deposits."
                    }',
                    'type' => 'checkbox',
                    'order' => 53,
                    'group' => 'Payments',
                ],
                [
                    'key' => 'payments.noxpay_recurring_disabled',
                    'display_name' => 'Disable for recurring payments',
                    'value' => 0,
                    'details' => '{
                        "true" : "On",
                        "false" : "Off",
                        "checked" : false,
                        "description": "Won`t be available for subscription payments, but it`s still available for deposits and one time payments."
                    }',
                    'type' => 'checkbox',
                    'order' => 54,
                    'group' => 'Payments',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropColumn([
                    'noxpay_payment_code',
                    'noxpay_payment_txid',
                    'noxpay_qr_code',
                    'noxpay_qr_code_text',
                    'noxpay_payment_url',
                ]);
            });
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->whereIn('key', [
                    'payments.noxpay_api_key',
                    'payments.noxpay_checkout_disabled',
                    'payments.noxpay_recurring_disabled',
                ])
                ->delete();
        }
    }
};
