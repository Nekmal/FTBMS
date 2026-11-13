<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('screen_id')->constrained('screens')->cascadeOnDelete();
            $table->foreignId('layout_id')->constrained('seating_layouts')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->enum('status', ['SCHEDULED', 'RUNNING', 'COMPLETED', 'CANCELLED'])->default('SCHEDULED');
            $table->timestamps();

            $table->index(['screen_id', 'starts_at', 'ends_at'], 'screen_schedule_idx');
            $table->index(['movie_id', 'starts_at']);
        });

        Schema::create('showtime_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showtime_id')->constrained('showtimes')->cascadeOnDelete();
            $table->enum('seat_type', ['STANDARD', 'PREMIUM', 'VIP'])->default('STANDARD');
            $table->unsignedBigInteger('price_minor'); // in Sri Lankan cents (e.g. 100000 = Rs. 1000.00)
            $table->timestamps();

            $table->unique(['showtime_id', 'seat_type'], 'unique_showtime_seat_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtime_prices');
        Schema::dropIfExists('showtimes');
    }
};

