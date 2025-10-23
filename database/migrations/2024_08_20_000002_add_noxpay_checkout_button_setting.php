<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            $exists = DB::table('settings')
                ->where('key', 'payments.noxpay_button_hidden')
                ->exists();

            if (! $exists) {
                DB::table('settings')->insert([
                    'key' => 'payments.noxpay_button_hidden',
                    'display_name' => 'Hide NoxPay checkout button',
                    'value' => 0,
                    'details' => '{
                        "true" : "On",
                        "false" : "Off",
                        "checked" : false,
                        "description": "Hide the dedicated NoxPay checkout button while keeping the provider available."
                    }',
                    'type' => 'checkbox',
                    'order' => 55,
                    'group' => 'Payments',
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('key', 'payments.noxpay_button_hidden')
                ->delete();
        }
    }
};
