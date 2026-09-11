<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Tenant-submitted proof of payment (private disk, same pattern as
            // verification_documents / application documents).
            $table->string('proof_path')->nullable()->after('reference_number');
            $table->string('proof_original_filename')->nullable()->after('proof_path');

            // When the tenant actually submitted this payment for review.
            // Distinct from payment_date (which is the date they say they paid).
            $table->timestamp('submitted_at')->nullable()->after('payment_date');

            // Who reviewed the submission (owner, manager, or super admin) and when.
            $table->foreignId('reviewed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            // Rejection reason or "please correct and resubmit" note.
            $table->text('review_reason')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'proof_path', 'proof_original_filename', 'submitted_at',
                'reviewed_at', 'review_reason',
            ]);
        });
    }
};
