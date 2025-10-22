<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $settings = [
            [
                'key' => 'payments.noxpay_crossramp_enabled',
                'display_name' => 'Enable Crossramp checkout',
                'value' => 0,
                'details' => '{
                    "true": "On",
                    "false": "Off",
                    "checked": false,
                    "description": "When enabled, NoxPay transactions will use the Crossramp checkout instead of the legacy PIX QR flow."
                }',
                'type' => 'checkbox',
                'order' => 55,
                'group' => 'Payments',
            ],
            [
                'key' => 'payments.noxpay_crossramp_template_id',
                'display_name' => 'Crossramp Template ID',
                'value' => null,
                'details' => null,
                'type' => 'text',
                'order' => 56,
                'group' => 'Payments',
            ],
            [
                'key' => 'payments.noxpay_crossramp_process',
                'display_name' => 'Crossramp Process',
                'value' => 'onramp',
                'details' => '{
                    "default": "onramp",
                    "options": {
                        "onramp": "Onramp",
                        "onramp_instant": "Onramp Instant",
                        "offramp": "Offramp",
                        "offramp_instant": "Offramp Instant"
                    }
                }',
                'type' => 'select_dropdown',
                'order' => 57,
                'group' => 'Payments',
            ],
            [
                'key' => 'payments.noxpay_crossramp_currency_from',
                'display_name' => 'Crossramp Entry Currency',
                'value' => 'BRL',
                'details' => null,
                'type' => 'text',
                'order' => 58,
                'group' => 'Payments',
            ],
            [
                'key' => 'payments.noxpay_crossramp_currency_to',
                'display_name' => 'Crossramp Exit Currency',
                'value' => 'USDT',
                'details' => null,
                'type' => 'text',
                'order' => 59,
                'group' => 'Payments',
            ],
            [
                'key' => 'payments.noxpay_crossramp_wallet',
                'display_name' => 'Default wallet for Crossramp',
                'value' => null,
                'details' => '{"description": "Optional wallet address used for onramp or offramp instant processes."}',
                'type' => 'text',
                'order' => 60,
                'group' => 'Payments',
            ],
            [
                'key' => 'payments.noxpay_crossramp_return_url',
                'display_name' => 'Crossramp Return URL',
                'value' => null,
                'details' => '{"description": "Overrides the default return URL used after the checkout is completed."}',
                'type' => 'text',
                'order' => 61,
                'group' => 'Payments',
            ],
            [
                'key' => 'payments.noxpay_crossramp_base_url',
                'display_name' => 'Crossramp API Base URL',
                'value' => null,
                'details' => '{"description": "Optional base URL override for the Crossramp API."}',
                'type' => 'text',
                'order' => 62,
                'group' => 'Payments',
            ],
            [
                'key' => 'payments.noxpay_crossramp_extra_payload',
                'display_name' => 'Crossramp Extra Payload (JSON)',
                'value' => null,
                'details' => '{"language": "json", "description": "Optional JSON merged into the Crossramp payload."}',
                'type' => 'code_editor',
                'order' => 63,
                'group' => 'Payments',
            ],
        ];

        foreach ($settings as $setting) {
            if (!DB::table('settings')->where('key', $setting['key'])->exists()) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->whereIn('key', [
                'payments.noxpay_crossramp_enabled',
                'payments.noxpay_crossramp_template_id',
                'payments.noxpay_crossramp_process',
                'payments.noxpay_crossramp_currency_from',
                'payments.noxpay_crossramp_currency_to',
                'payments.noxpay_crossramp_wallet',
                'payments.noxpay_crossramp_return_url',
                'payments.noxpay_crossramp_base_url',
                'payments.noxpay_crossramp_extra_payload',
            ])
            ->delete();
    }
};
