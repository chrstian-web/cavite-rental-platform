<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scene_hotspots', function (Blueprint $table) {
            // Arrow diameter in pixels (owner-adjustable per hotspot). 56 = the original size.
            $table->unsignedSmallInteger('size')->default(56)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('scene_hotspots', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }
};
