<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fast-access cache of "where is this owner in the verification pipeline,"
        // kept in sync with the latest owner_verifications row. Null for
        // non-owner roles, where the concept doesn't apply.
        Schema::table('users', function (Blueprint $table) {
            $table->string('owner_verification_status')->nullable()->after('status');
            // not_submitted | needs_review | needs_additional_documents |
            // rejected | approved | otp_required | verified
        });

        // One row per verification ATTEMPT, so a rejected-then-resubmitted
        // owner has an auditable history rather than one row being overwritten.
        Schema::create('owner_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status'); // needs_review | needs_additional_documents | rejected | approved
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_verification_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            // government_id | business_permit | barangay_clearance | mayors_permit |
            // dti_sec_registration | proof_of_ownership | other
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('status')->default('pending'); // pending | verified | rejected
            $table->date('expiration_date')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
        Schema::dropIfExists('owner_verifications');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('owner_verification_status');
        });
    }
};
