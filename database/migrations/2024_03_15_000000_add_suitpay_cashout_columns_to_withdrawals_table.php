<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSuitpayCashoutColumnsToWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('withdrawals')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                if (!Schema::hasColumn('withdrawals', 'pix_key')) {
                    $table->string('pix_key')->nullable()->after('payment_identifier');
                }
                if (!Schema::hasColumn('withdrawals', 'pix_key_type')) {
                    $table->string('pix_key_type')->nullable()->after('pix_key');
                }
                if (!Schema::hasColumn('withdrawals', 'pix_document')) {
                    $table->string('pix_document')->nullable()->after('pix_key_type');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_external_id')) {
                    $table->string('suitpay_cashout_external_id')->nullable()->after('pix_document');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_transaction_id')) {
                    $table->string('suitpay_cashout_transaction_id')->nullable()->after('suitpay_cashout_external_id');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_status')) {
                    $table->string('suitpay_cashout_status')->nullable()->after('suitpay_cashout_transaction_id');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_message')) {
                    $table->text('suitpay_cashout_message')->nullable()->after('suitpay_cashout_status');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_value')) {
                    $table->decimal('suitpay_cashout_value', 12, 2)->nullable()->after('suitpay_cashout_message');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_payload')) {
                    $table->longText('suitpay_cashout_payload')->nullable()->after('suitpay_cashout_value');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_receipt')) {
                    $table->longText('suitpay_cashout_receipt')->nullable()->after('suitpay_cashout_payload');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_requested_at')) {
                    $table->timestamp('suitpay_cashout_requested_at')->nullable()->after('suitpay_cashout_receipt');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_confirmed_at')) {
                    $table->timestamp('suitpay_cashout_confirmed_at')->nullable()->after('suitpay_cashout_requested_at');
                }
                if (!Schema::hasColumn('withdrawals', 'suitpay_cashout_receipt_generated_at')) {
                    $table->timestamp('suitpay_cashout_receipt_generated_at')->nullable()->after('suitpay_cashout_confirmed_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('withdrawals')) {
            Schema::table('withdrawals', function (Blueprint $table) {
                $columns = [
                    'pix_key',
                    'pix_key_type',
                    'pix_document',
                    'suitpay_cashout_external_id',
                    'suitpay_cashout_transaction_id',
                    'suitpay_cashout_status',
                    'suitpay_cashout_message',
                    'suitpay_cashout_value',
                    'suitpay_cashout_payload',
                    'suitpay_cashout_receipt',
                    'suitpay_cashout_requested_at',
                    'suitpay_cashout_confirmed_at',
                    'suitpay_cashout_receipt_generated_at',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('withdrawals', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
}
