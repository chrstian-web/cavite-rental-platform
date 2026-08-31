<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');       // Wifi, Parking, Aircon, CCTV, Water Refill...
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->string('category')->nullable(); // utility, safety, comfort, lifestyle
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenities');
    }
};
