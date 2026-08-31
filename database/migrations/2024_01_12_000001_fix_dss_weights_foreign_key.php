<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The original dss_weights migration let Laravel auto-guess the foreign
     * key's target table, which incorrectly pluralized "dss_criteria" (already
     * plural) into "dss_criterias" — a table that never existed. This
     * recreates dss_weights with the foreign key pointed at the correct
     * table. Safe to run on a database that already has other data: it only
     * touches dss_weights, which — if you hit this bug — never successfully
     * held any rows anyway.
     */
    public function up(): void
    {
        Schema::dropIfExists('dss_weights');

        Schema::create('dss_weights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dss_criteria_id')->constrained(table: 'dss_criteria')->cascadeOnDelete();
            $table->decimal('weight_percentage', 5, 2);
            $table->json('scoring_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['dss_criteria_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dss_weights');
    }
};
