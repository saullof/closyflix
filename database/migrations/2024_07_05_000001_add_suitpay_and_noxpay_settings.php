<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = [
            'payments.suitpay_enabled' => [
                'display_name' => 'Enable SuitPay',
                'value' => 0,
                'details' => '{
                    "true" : "On",
                    "false" : "Off",
                    "checked" : false,
                    "description": "Toggle SuitPay availability across the platform."
                }',
                'type' => 'checkbox',
                'order' => 31,
                'group' => 'Payments',
            ],
            'payments.suitpay_client_id' => [
                'display_name' => 'SuitPay Client ID',
                'value' => null,
                'details' => null,
                'type' => 'text',
                'order' => 32,
                'group' => 'Payments',
            ],
            'payments.suitpay_client_secret' => [
                'display_name' => 'SuitPay Client Secret',
                'value' => null,
                'details' => null,
                'type' => 'text',
                'order' => 33,
                'group' => 'Payments',
            ],
            'payments.suitpay_split_username' => [
                'display_name' => 'SuitPay Split Username',
                'value' => null,
                'details' => null,
                'type' => 'text',
                'order' => 34,
                'group' => 'Payments',
            ],
            'payments.suitpay_split_percentage' => [
                'display_name' => 'SuitPay Split Percentage',
                'value' => null,
                'details' => null,
                'type' => 'text',
                'order' => 35,
                'group' => 'Payments',
            ],
            'payments.noxpay_enabled' => [
                'display_name' => 'Enable NoxPay',
                'value' => 0,
                'details' => '{
                    "true" : "On",
                    "false" : "Off",
                    "checked" : false,
                    "description": "Toggle NoxPay availability across the platform."
                }',
                'type' => 'checkbox',
                'order' => 55,
                'group' => 'Payments',
            ],
            'payments.noxpay_webhook_secret' => [
                'display_name' => 'NoxPay Webhook Secret',
                'value' => null,
                'details' => null,
                'type' => 'text',
                'order' => 56,
                'group' => 'Payments',
            ],
        ];

        foreach ($settings as $key => $setting) {
            $exists = DB::table('settings')->where('key', $key)->exists();

            if ($exists) {
                $updateData = $setting;
                unset($updateData['value']);
                DB::table('settings')->where('key', $key)->update($updateData);
            } else {
                DB::table('settings')->insert(array_merge(['key' => $key], $setting));
            }
        }

        DB::table('settings')
            ->where('key', 'payments.nowpayments_api_key')
            ->update(['display_name' => 'SuitPay API Key']);

        DB::table('settings')
            ->where('key', 'payments.nowpayments_ipn_secret_key')
            ->update(['display_name' => 'SuitPay IPN Secret Key']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('key', [
            'payments.suitpay_enabled',
            'payments.suitpay_client_id',
            'payments.suitpay_client_secret',
            'payments.suitpay_split_username',
            'payments.suitpay_split_percentage',
            'payments.noxpay_enabled',
            'payments.noxpay_webhook_secret',
        ])->delete();

        DB::table('settings')
            ->where('key', 'payments.nowpayments_api_key')
            ->update(['display_name' => 'NowPayments Api Key']);

        DB::table('settings')
            ->where('key', 'payments.nowpayments_ipn_secret_key')
            ->update(['display_name' => 'NowPayments IPN Secret Key']);
    }
};
