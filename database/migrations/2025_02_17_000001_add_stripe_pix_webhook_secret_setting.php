<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!DB::table('settings')->where('key', 'payments.stripe_pix_webhook_secret')->exists()) {
            DB::table('settings')->insert([
                'key' => 'payments.stripe_pix_webhook_secret',
                'display_name' => 'Stripe Pix Webhook Secret',
                'value' => null,
                'details' => json_encode([
                    'description' => 'Webhook signing secret for the Stripe Pix account.',
                ]),
                'type' => 'text',
                'order' => 43,
                'group' => 'Payments',
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'payments.stripe_pix_webhook_secret')->delete();
    }
};
