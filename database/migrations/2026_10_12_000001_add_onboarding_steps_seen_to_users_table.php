<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // {"favorite": "2026-10-12T08:00:00+00:00", ...}: which "Getting started" tutorials the
            // user has opened. Stored per user so the ticks follow them across devices.
            $table->json('onboarding_steps_seen')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_steps_seen');
        });
    }
};
