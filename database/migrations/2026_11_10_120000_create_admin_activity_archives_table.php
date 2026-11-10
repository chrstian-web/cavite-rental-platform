<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_activity_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('activity_type', 50);
            $table->unsignedBigInteger('activity_id');
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->unique(['admin_id', 'activity_type', 'activity_id']);
            $table->index(['activity_type', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_archives');
    }
};
