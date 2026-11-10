<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 20);
            $table->unsignedInteger('amount')->default(0);
            // pending (awaiting payment) | active | expired | cancelled
            $table->string('status', 20)->default('pending');
            $table->string('gateway', 20)->nullable();
            $table->string('gateway_checkout_id')->nullable()->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('properties', function (Blueprint $table) {
            // Set by the plan limit, never by the owner. Hidden listings stay in the owner's list.
            $table->boolean('subscription_hidden')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('subscription_hidden');
        });

        Schema::dropIfExists('subscriptions');
    }
};
