<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Municipalities/Cities within Cavite (or any province, kept generic on purpose)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('province')->default('Cavite');
            $table->string('city_municipality'); // e.g. Dasmarinas, Imus, General Trias
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['province', 'city_municipality']);
            $table->index('city_municipality');
        });

        Schema::create('barangays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['location_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barangays');
        Schema::dropIfExists('locations');
    }
};
