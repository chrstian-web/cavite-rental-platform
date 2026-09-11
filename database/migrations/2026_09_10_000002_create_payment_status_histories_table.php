<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only audit trail: one row per status transition, so
        // "who changed what, when, and why" is never overwritten. Mirrors
        // the reasoning behind owner_verifications being attempt-based.
        Schema::create('payment_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();

            // Null actor = system-driven transition (e.g. an automated overdue sweep).
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status_histories');
    }
};
