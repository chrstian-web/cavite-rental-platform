<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_space_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('status')->default('draft'); // draft | published | archived
            $table->timestamps();
        });

        Schema::create('virtual_tour_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_tour_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('panorama_image');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['virtual_tour_id', 'sort_order']);
        });

        Schema::create('scene_hotspots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained('virtual_tour_scenes')->cascadeOnDelete();
            $table->foreignId('target_scene_id')->nullable()->constrained('virtual_tour_scenes')->nullOnDelete();
            $table->decimal('position_x', 10, 5);
            $table->decimal('position_y', 10, 5);
            $table->decimal('position_z', 10, 5)->nullable();
            $table->string('label')->nullable();
            $table->string('type')->default('navigation'); // navigation | info
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scene_hotspots');
        Schema::dropIfExists('virtual_tour_scenes');
        Schema::dropIfExists('virtual_tours');
    }
};
