<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seat_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token', 64)->nullable()->index();
            $table->foreignId('showtime_id')->constrained('showtimes')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->enum('status', ['ACTIVE', 'EXPIRED', 'RELEASED', 'CONVERTED'])->default('ACTIVE');
            $table->timestamps();

            $table->index(['showtime_id', 'status', 'expires_at'], 'active_holds_idx');
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->foreignId('showtime_id')->constrained('showtimes')->restrictOnDelete();
            $table->enum('status', ['PENDING', 'CONFIRMED', 'CANCELLED', 'EXPIRED'])->default('PENDING');
            $table->unsignedBigInteger('total_minor')->default(0);
            $table->enum('booking_type', ['ONLINE', 'COUNTER'])->default('ONLINE');
            $table->foreignId('created_by_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['showtime_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('show_seats', function (Blueprint $table) {
            $table->id();

            $table->foreignId('showtime_id')
                ->constrained('showtimes')
                ->restrictOnDelete();

            $table->foreignId('seat_id')
                ->constrained('seats')
                ->restrictOnDelete();

            $table->enum('status', [
                'AVAILABLE',
                'HELD',
                'SOLD',
                'BLOCKED'
            ])->default('AVAILABLE');

            $table->foreignId('seat_hold_id')
                ->nullable()
                ->constrained('seat_holds')
                ->nullOnDelete();

            $table->foreignId('booking_id')
                ->nullable()
                ->constrained('bookings')
                ->nullOnDelete();

            $table->timestamp('hold_expires_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['showtime_id', 'seat_id'],
                'unique_showtime_seat'
            );

            $table->index([
                'showtime_id',
                'status'
            ]);
        });

        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained('seats')->restrictOnDelete();
            $table->unsignedBigInteger('price_minor');
            $table->timestamps();

            $table->unique(['booking_id', 'seat_id'], 'unique_booking_seat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
        Schema::dropIfExists('show_seats');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('seat_holds');
    }
};

