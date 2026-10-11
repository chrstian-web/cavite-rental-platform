<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Null = not yet. Stored per user so the welcome/tour follow them across devices.
            $table->timestamp('onboarding_welcomed_at')->nullable();
            $table->timestamp('onboarding_tour_completed_at')->nullable();
            $table->timestamp('onboarding_checklist_dismissed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_welcomed_at',
                'onboarding_tour_completed_at',
                'onboarding_checklist_dismissed_at',
            ]);
        });
    }
};
