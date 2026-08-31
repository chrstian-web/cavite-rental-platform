<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_application_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // tenant
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_space_id')->constrained()->cascadeOnDelete();

            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('security_deposit', 10, 2)->default(0);
            $table->decimal('advance_payment', 10, 2)->default(0);

            $table->date('start_date');
            $table->date('end_date');

            $table->longText('terms_and_conditions')->nullable();
            $table->string('contract_pdf_path')->nullable();

            // draft | active | expired | terminated
            $table->string('status')->default('draft');

            $table->timestamps();

            $table->index(['status', 'end_date']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // tenant who paid

            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->date('payment_date')->nullable();

            $table->string('payment_method')->nullable(); // cash, gcash, bank_transfer, other
            $table->string('reference_number')->nullable();

            // pending | paid | overdue | failed
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['rental_contract_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // tenant
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rental_space_id')->nullable()->constrained()->nullOnDelete();

            // plumbing | electrical | internet | furniture | air_conditioning | cleaning | security | other
            $table->string('category');
            $table->text('description');
            $table->string('priority')->default('normal'); // low | normal | high | urgent

            // submitted | in_progress | resolved | closed
            $table->string('status')->default('submitted');

            $table->timestamps();

            $table->index(['status', 'priority']);
        });

        Schema::create('maintenance_request_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_images');
        Schema::dropIfExists('maintenance_requests');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('rental_contracts');
    }
};
