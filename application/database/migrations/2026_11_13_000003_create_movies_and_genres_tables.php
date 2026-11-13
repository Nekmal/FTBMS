<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->unsignedInteger('duration_minutes')->default(120);
            $table->text('description')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('trailer_url')->nullable();
            $table->enum('status', ['COMING_SOON', 'NOW_SHOWING', 'ENDED'])->default('NOW_SHOWING');
            $table->date('release_date')->nullable();
            $table->string('language')->default('English');
            $table->string('rating')->default('PG-13');
            $table->timestamps();
        });

        Schema::create('genre_movie', function (Blueprint $table) {
            $table->foreignId('movie_id')->constrained('movies')->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained('genres')->cascadeOnDelete();
            $table->primary(['movie_id', 'genre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genre_movie');
        Schema::dropIfExists('movies');
        Schema::dropIfExists('genres');
    }
};
