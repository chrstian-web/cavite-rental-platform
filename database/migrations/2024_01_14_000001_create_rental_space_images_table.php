<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-unit photos, separate from property_images (Step 1) — a property
     * photo shows the building/common areas, a rental_space photo shows
     * the SPECIFIC room/unit a renter would actually move into.
     */
    public function up(): void
    {
        Schema::create('rental_space_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_space_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_cover')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['rental_space_id', 'is_cover']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_space_images');
    }
};
