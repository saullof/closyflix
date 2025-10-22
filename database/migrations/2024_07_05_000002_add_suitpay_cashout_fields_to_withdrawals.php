<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('withdrawals')) {
            return;
        }

        Schema::table('withdrawals', function (Blueprint $table) {
            if (! Schema::hasColumn('withdrawals', 'pix_key_type')) {
                $table->string('pix_key_type')->nullable()->after('payment_identifier');
            }

            if (! Schema::hasColumn('withdrawals', 'pix_beneficiary_name')) {
                $table->string('pix_beneficiary_name')->nullable()->after('pix_key_type');
            }

            if (! Schema::hasColumn('withdrawals', 'pix_document')) {
                $table->string('pix_document')->nullable()->after('pix_beneficiary_name');
            }

            if (! Schema::hasColumn('withdrawals', 'suitpay_cashout_id')) {
                $table->string('suitpay_cashout_id')->nullable()->after('pix_document');
            }

            if (! Schema::hasColumn('withdrawals', 'suitpay_cashout_status')) {
                $table->string('suitpay_cashout_status')->nullable()->after('suitpay_cashout_id');
            }

            if (! Schema::hasColumn('withdrawals', 'suitpay_cashout_payload')) {
                $table->json('suitpay_cashout_payload')->nullable()->after('suitpay_cashout_status');
            }

            if (! Schema::hasColumn('withdrawals', 'suitpay_cashout_response')) {
                $table->json('suitpay_cashout_response')->nullable()->after('suitpay_cashout_payload');
            }

            if (! Schema::hasColumn('withdrawals', 'suitpay_cashout_error')) {
                $table->text('suitpay_cashout_error')->nullable()->after('suitpay_cashout_response');
            }

            if (! Schema::hasColumn('withdrawals', 'suitpay_cashout_requested_at')) {
                $table->timestamp('suitpay_cashout_requested_at')->nullable()->after('suitpay_cashout_error');
            }

            if (! Schema::hasColumn('withdrawals', 'suitpay_cashout_processed_at')) {
                $table->timestamp('suitpay_cashout_processed_at')->nullable()->after('suitpay_cashout_requested_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('withdrawals')) {
            return;
        }

        Schema::table('withdrawals', function (Blueprint $table) {
            $columns = [
                'pix_key_type',
                'pix_beneficiary_name',
                'pix_document',
                'suitpay_cashout_id',
                'suitpay_cashout_status',
                'suitpay_cashout_payload',
                'suitpay_cashout_response',
                'suitpay_cashout_error',
                'suitpay_cashout_requested_at',
                'suitpay_cashout_processed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('withdrawals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
