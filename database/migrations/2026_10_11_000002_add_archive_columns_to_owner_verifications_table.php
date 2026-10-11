<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_verifications', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('reviewed_at');
            $table->timestamp('files_purged_at')->nullable()->after('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('owner_verifications', function (Blueprint $table) {
            $table->dropColumn(['archived_at', 'files_purged_at']);
        });
    }
};
