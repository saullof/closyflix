<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!DB::table('settings')->where('key', 'payments.stripe_pix_webhook_secret')->exists()) {
            DB::table('settings')->insert([
                'key' => 'payments.stripe_pix_webhook_secret',
                'display_name' => 'Stripe PIX Segredo Webhook',
                'value' => null,
                'details' => json_encode([
                    'description' => 'Informe o segredo do webhook da conta Stripe Pix.',
                ]),
                'type' => 'text',
                'order' => 43,
                'group' => 'Payments',
            ]);
        } else {
            DB::table('settings')
                ->where('key', 'payments.stripe_pix_webhook_secret')
                ->update([
                    'display_name' => 'Stripe PIX Segredo Webhook',
                    'details' => json_encode([
                        'description' => 'Informe o segredo do webhook da conta Stripe Pix.',
                    ]),
                    'type' => 'text',
                    'group' => 'Payments',
                ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'payments.stripe_pix_webhook_secret')->delete();
    }
};
