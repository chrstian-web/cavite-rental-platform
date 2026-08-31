<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            // ties a review to a specific completed stay so a tenant can't spam reviews
            $table->foreignId('rental_contract_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('rating');            // overall 1-5
            $table->unsignedTinyInteger('cleanliness_rating')->nullable();
            $table->unsignedTinyInteger('location_rating')->nullable();
            $table->unsignedTinyInteger('amenities_rating')->nullable();
            $table->unsignedTinyInteger('value_rating')->nullable();
            $table->unsignedTinyInteger('management_rating')->nullable();

            $table->text('comment')->nullable();
            $table->timestamps();

            // one review per completed contract
            $table->unique(['user_id', 'rental_contract_id']);
            $table->index(['property_id', 'rating']);
        });

        // Standard Laravel notifications table (works out-of-the-box with Notification facade)
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reviews');
    }
};
