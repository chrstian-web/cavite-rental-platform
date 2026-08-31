<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unified "rental space" table.
     *
     * Rather than building three separate schemas for condo units, boarding-house
     * rooms, and dormitory rooms, we model every rentable space with one table.
     * Fields that are common across all three types are first-class columns
     * (searchable/indexable). Fields that only apply to one property type live
     * inside the `attributes` JSON column so the schema never has to change
     * when a new property type or type-specific field is introduced.
     *
     * Examples of what goes in `attributes`:
     *  - condominium:   floor, unit_type, floor_area_sqm, furnishing
     *  - boarding_house: room_type, utilities_included
     *  - dormitory:      gender_restriction, curfew_time, utilities_included
     */
    public function up(): void
    {
        Schema::create('rental_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();

            $table->string('space_number');      // Unit/Room number label, e.g. "3B", "Room 12"
            $table->string('space_type')->nullable(); // studio, 1-bedroom, single, shared, etc.

            $table->unsignedTinyInteger('bedrooms')->default(0);
            $table->unsignedTinyInteger('bathrooms')->default(0);

            $table->unsignedInteger('total_capacity')->default(1); // total beds/occupant slots
            $table->unsignedInteger('occupied_capacity')->default(0);

            $table->decimal('floor_area_sqm', 8, 2)->nullable();
            $table->boolean('is_furnished')->default(false);

            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('security_deposit', 10, 2)->default(0);
            $table->decimal('advance_payment', 10, 2)->default(0);

            // available | occupied | reserved | maintenance | inactive
            $table->string('status')->default('available');

            $table->json('attributes')->nullable(); // type-specific extra fields
            $table->json('utilities_included')->nullable(); // ['water','electricity','wifi']

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['property_id', 'space_number']);
            $table->index(['status', 'monthly_rent']);
            $table->index(['bedrooms', 'bathrooms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_spaces');
    }
};
