<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_type')->default('monthly_rent')->after('user_id');
            $table->string('payment_source')->default('manual')->after('payment_method');
            $table->string('gateway')->nullable()->after('payment_source');
            $table->string('gateway_payment_id')->nullable()->after('gateway');
            $table->string('gateway_checkout_id')->nullable()->after('gateway_payment_id');
            $table->string('gateway_reference')->nullable()->after('gateway_checkout_id');
            $table->string('currency', 3)->default('PHP')->after('amount');
            $table->json('metadata')->nullable()->after('notes');
            $table->timestamp('paid_at')->nullable()->after('submitted_at');
            $table->timestamp('failed_at')->nullable()->after('paid_at');
            $table->timestamp('refunded_at')->nullable()->after('failed_at');
            $table->string('receipt_number')->nullable()->unique()->after('refunded_at');
            $table->date('billing_period_start')->nullable()->after('due_date');
            $table->date('billing_period_end')->nullable()->after('billing_period_start');
            $table->unsignedSmallInteger('advance_months')->nullable()->after('billing_period_end');
            $table->decimal('refund_amount', 10, 2)->nullable()->after('advance_months');
            $table->date('refund_date')->nullable()->after('refund_amount');
            $table->text('refund_reason')->nullable()->after('refund_date');

            $table->index(['rental_contract_id', 'payment_type', 'billing_period_start'], 'payments_billing_lookup');
            $table->index(['gateway', 'gateway_checkout_id'], 'payments_gateway_checkout_lookup');
            $table->index(['payment_source', 'status'], 'payments_source_status_lookup');
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('source')->default('system');
            $table->string('gateway_event_id')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['payment_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_billing_lookup');
            $table->dropIndex('payments_gateway_checkout_lookup');
            $table->dropIndex('payments_source_status_lookup');
            $table->dropUnique(['receipt_number']);
            $table->dropColumn([
                'payment_type', 'payment_source', 'gateway', 'gateway_payment_id',
                'gateway_checkout_id', 'gateway_reference', 'currency', 'metadata',
                'paid_at', 'failed_at', 'refunded_at', 'receipt_number',
                'billing_period_start', 'billing_period_end', 'advance_months',
                'refund_amount', 'refund_date', 'refund_reason',
            ]);
        });
    }
};

