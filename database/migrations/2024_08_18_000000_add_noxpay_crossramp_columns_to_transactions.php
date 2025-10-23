<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('transactions', 'noxpay_checkout_end2end')) {
                    $table->string('noxpay_checkout_end2end')->nullable()->after('noxpay_payment_url');
                }

                if (!Schema::hasColumn('transactions', 'noxpay_checkout_status')) {
                    $table->string('noxpay_checkout_status')->nullable()->after('noxpay_checkout_end2end');
                }

                if (!Schema::hasColumn('transactions', 'noxpay_checkout_substatus')) {
                    $table->string('noxpay_checkout_substatus')->nullable()->after('noxpay_checkout_status');
                }

                if (!Schema::hasColumn('transactions', 'noxpay_checkout_process')) {
                    $table->string('noxpay_checkout_process')->nullable()->after('noxpay_checkout_substatus');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $columns = [
                    'noxpay_checkout_process',
                    'noxpay_checkout_substatus',
                    'noxpay_checkout_status',
                    'noxpay_checkout_end2end',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('transactions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
