<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'payments.nowpayments_api_key')
            ->update(['display_name' => 'Suitpay Client ID']);

        DB::table('settings')
            ->where('key', 'payments.nowpayments_ipn_secret_key')
            ->update(['display_name' => 'Suitpay Client Secret']);

        if (!DB::table('settings')->where('key', 'payments.noxpay_api_key')->exists()) {
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
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'payments.nowpayments_api_key')
            ->update(['display_name' => 'NowPayments Api Key']);

        DB::table('settings')
            ->where('key', 'payments.nowpayments_ipn_secret_key')
            ->update(['display_name' => 'NowPayments IPN Secret Key']);

        DB::table('settings')
            ->whereIn('key', [
                'payments.noxpay_api_key',
                'payments.noxpay_checkout_disabled',
                'payments.noxpay_recurring_disabled',
            ])
            ->delete();
    }
};
