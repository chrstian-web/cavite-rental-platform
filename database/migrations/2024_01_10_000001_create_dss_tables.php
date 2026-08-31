<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Decision Support System configuration + results.
     *
     * dss_criteria  -> the fixed list of scoring criteria (budget, location, amenities, ...)
     * dss_weights   -> the CURRENT active weight (%) per criterion, editable by admins,
     *                  versioned so historical scores stay explainable.
     * dss_scores    -> a computed, persisted recommendation score for a user/property pair,
     *                  with a snapshot of the per-criterion breakdown and human-readable reasons.
     */
    public function up(): void
    {
        Schema::create('dss_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // budget, location, amenities, property_type, capacity, distance, furnishing
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dss_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dss_criteria_id')->constrained(table: 'dss_criteria')->cascadeOnDelete();
            $table->decimal('weight_percentage', 5, 2); // e.g. 30.00 for 30%
            $table->json('scoring_rules')->nullable();  // min_requirement, preferred_value, curve, etc.
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['dss_criteria_id', 'is_active']);
        });

        Schema::create('dss_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_space_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('total_score', 5, 2); // 0-100
            $table->json('criteria_breakdown');   // {"budget": {"score":90,"weight":30,"weighted":27}, ...}
            $table->json('reasons');              // ["Within your ₱10,000 budget", ...]
            $table->json('preferences_snapshot'); // the renter preferences used to compute this score

            $table->timestamps();

            $table->index(['user_id', 'total_score']);
            $table->index(['property_id', 'total_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_scores');
        Schema::dropIfExists('dss_weights');
        Schema::dropIfExists('dss_criteria');
    }
};
