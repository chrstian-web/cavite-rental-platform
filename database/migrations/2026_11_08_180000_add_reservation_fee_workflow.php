<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasDuplicateContracts = DB::table('rental_contracts')
            ->select('rental_application_id')
            ->whereNotNull('rental_application_id')
            ->groupBy('rental_application_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        if ($hasDuplicateContracts) {
            throw new \RuntimeException('Resolve duplicate contracts for the same rental application before enabling the reservation-fee workflow.');
        }

        Schema::table('rental_applications', function (Blueprint $table) {
            $table->decimal('reservation_fee', 10, 2)->nullable()->after('lease_end_date');
            $table->longText('reservation_terms')->nullable()->after('reservation_fee');
            $table->timestamp('reservation_offer_sent_at')->nullable()->after('reservation_terms');
            $table->timestamp('reservation_terms_accepted_at')->nullable()->after('reservation_offer_sent_at');
            $table->string('reservation_terms_accepted_hash', 64)->nullable()->after('reservation_terms_accepted_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['rental_contract_id']);
            $table->foreignId('rental_contract_id')->nullable()->change();
            $table->foreignId('rental_application_id')->nullable()->after('rental_contract_id')->constrained()->cascadeOnDelete();
            $table->foreign('rental_contract_id')->references('id')->on('rental_contracts')->cascadeOnDelete();
            $table->index(['rental_application_id', 'payment_type', 'status'], 'payments_reservation_lookup');
            $table->unique(['rental_application_id', 'payment_type'], 'payments_application_type_unique');
        });

        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->unique('rental_application_id', 'rental_contracts_application_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('payments')->whereNotNull('rental_application_id')->exists()) {
            throw new \RuntimeException('Cannot roll back reservation-fee support while application-linked reservation payments exist. Resolve these payment records first.');
        }

        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropUnique('rental_contracts_application_unique');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_application_type_unique');
            $table->dropIndex('payments_reservation_lookup');
            $table->dropForeign(['rental_application_id']);
            $table->dropColumn('rental_application_id');
            $table->dropForeign(['rental_contract_id']);
            $table->foreignId('rental_contract_id')->nullable(false)->change();
            $table->foreign('rental_contract_id')->references('id')->on('rental_contracts')->cascadeOnDelete();
        });

        Schema::table('rental_applications', function (Blueprint $table) {
            $table->dropColumn([
                'reservation_fee', 'reservation_terms', 'reservation_offer_sent_at',
                'reservation_terms_accepted_at', 'reservation_terms_accepted_hash',
            ]);
        });
    }
};
