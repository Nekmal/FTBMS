<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->enum('method', ['ONLINE_MOCK', 'CASH'])->default('ONLINE_MOCK');
            $table->unsignedBigInteger('amount_minor');
            $table->enum('status', ['PENDING', 'SUCCESS', 'FAILED'])->default('PENDING');
            $table->string('transaction_reference', 64)->unique();
            $table->foreignId('staff_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('cash_received_minor')->nullable();
            $table->unsignedBigInteger('cash_returned_minor')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->enum('status', ['PENDING', 'COMPLETED', 'FAILED'])->default('PENDING');
            $table->string('reason')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_item_id')->constrained('booking_items')->cascadeOnDelete();
            $table->string('ticket_code', 32)->unique();
            $table->string('qr_token_hash', 64)->unique();
            $table->enum('status', ['ISSUED', 'VALIDATED', 'CANCELLED'])->default('ISSUED');
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ticket_code', 'status']);
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->enum('type', ['PERCENTAGE', 'FIXED'])->default('PERCENTAGE');
            $table->unsignedBigInteger('discount_value'); // percentage (e.g. 10 for 10%) or minor cents
            $table->unsignedBigInteger('min_spend_minor')->default(0);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->foreignId('cinema_id')->nullable()->constrained('cinemas')->nullOnDelete();
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['action', 'entity_type']);
            $table->index(['cinema_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
