<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_space_id')->constrained()->cascadeOnDelete();

            $table->date('desired_move_in_date');
            $table->unsignedInteger('length_of_stay_months')->nullable();
            $table->unsignedInteger('number_of_occupants')->default(1);

            $table->string('employment_status')->nullable(); // employed, self-employed, student, unemployed
            $table->decimal('monthly_income', 10, 2)->nullable();

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_number')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            $table->text('notes')->nullable();

            // pending | under_review | approved | rejected | cancelled
            $table->string('status')->default('pending');
            $table->text('decision_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_application_id')->constrained()->cascadeOnDelete();
            $table->string('document_type'); // valid_id, proof_of_income, school_id, coe, other
            $table->string('path');
            $table->string('original_filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('status')->default('pending'); // pending | verified | rejected
            $table->timestamps();
        });

        Schema::create('viewing_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_space_id')->nullable()->constrained()->nullOnDelete();

            $table->date('preferred_date');
            $table->time('preferred_time');
            $table->text('message')->nullable();

            // pending | confirmed | rescheduled | completed | cancelled
            $table->string('status')->default('pending');

            $table->timestamps();

            $table->index(['status', 'preferred_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viewing_requests');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('rental_applications');
    }
};
