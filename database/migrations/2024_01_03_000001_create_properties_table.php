<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            // condominium | boarding_house | dormitory
            $table->enum('property_type', ['condominium', 'boarding_house', 'dormitory']);
            $table->text('description')->nullable();

            $table->string('address_line');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('contact_email')->nullable();

            $table->decimal('min_monthly_rent', 10, 2)->nullable();
            $table->decimal('max_monthly_rent', 10, 2)->nullable();

            $table->json('house_rules')->nullable(); // array of rule strings

            // available | fully_booked | unavailable | under_review
            $table->string('availability_status')->default('available');
            // pending | verified | rejected
            $table->string('verification_status')->default('pending');

            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);

            $table->softDeletes();
            $table->timestamps();

            $table->index(['property_type', 'availability_status']);
            $table->index(['location_id', 'property_type']);
            $table->index(['min_monthly_rent', 'max_monthly_rent']);
            $table->index(['latitude', 'longitude']);
        });

        // Property Manager <-> Property assignment (many-to-many), now that properties exists
        Schema::create('property_manager_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['manager_id', 'property_id']);
        });

        Schema::create('property_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_cover')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['property_id', 'is_cover']);
        });

        Schema::create('amenity_property', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['property_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_property');
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('property_manager_assignments');
        Schema::dropIfExists('properties');
    }
};
