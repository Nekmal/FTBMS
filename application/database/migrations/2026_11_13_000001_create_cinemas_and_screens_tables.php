<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cinemas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cinema_id')->constrained('cinemas')->cascadeOnDelete();
            $table->string('name');
            $table->integer('total_capacity')->default(0);
            $table->timestamps();
        });

        Schema::create('seating_layouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained('screens')->cascadeOnDelete();
            $table->string('name')->default('Default Layout');
            $table->unsignedInteger('version')->default(1);
            $table->unsignedInteger('total_rows')->default(6);
            $table->unsignedInteger('seats_per_row')->default(10);
            $table->timestamps();
        });

        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_id')->constrained('seating_layouts')->cascadeOnDelete();
            $table->string('row_label', 10);
            $table->unsignedInteger('seat_number');
            $table->enum('seat_type', ['STANDARD', 'PREMIUM', 'VIP'])->default('STANDARD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['layout_id', 'row_label', 'seat_number'], 'unique_layout_row_seat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
        Schema::dropIfExists('seating_layouts');
        Schema::dropIfExists('screens');
        Schema::dropIfExists('cinemas');
    }
};
