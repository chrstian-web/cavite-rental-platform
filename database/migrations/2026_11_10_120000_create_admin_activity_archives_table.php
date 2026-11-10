<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A previous deploy crashed halfway and left an empty, half-built table
        // behind (the migration was never recorded as "Ran"). This table was never
        // used by live code, so it is safe to clear it and rebuild it properly.
        // Once this migration has run successfully, this line can be deleted.
        Schema::dropIfExists('admin_activity_archives');

        Schema::create('admin_activity_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('activity_type', 50);
            $table->unsignedBigInteger('activity_id');
            $table->timestamp('archived_at');
            $table->timestamps();

            // Explicit short names: Laravel's auto-generated name for this unique
            // rule was 65 characters, and MySQL only allows 64.
            $table->unique(['admin_id', 'activity_type', 'activity_id'], 'admin_activity_archives_unique');
            $table->index(['activity_type', 'activity_id'], 'admin_activity_archives_type_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_activity_archives');
    }
};
