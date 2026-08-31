<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lightweight search log, written whenever a public property search
     * includes at least one real filter (so plain unfiltered "/properties"
     * visits don't pollute "most searched locations" reporting).
     */
    public function up(): void
    {
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('property_type')->nullable();
            $table->decimal('min_rent', 10, 2)->nullable();
            $table->decimal('max_rent', 10, 2)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('location_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
