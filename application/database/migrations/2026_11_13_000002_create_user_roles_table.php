<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['ADMIN', 'CINEMA_MANAGER', 'COUNTER_STAFF', 'CUSTOMER'])->default('CUSTOMER');
            $table->foreignId('cinema_id')->nullable()->constrained('cinemas')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role', 'cinema_id'], 'unique_user_role_cinema');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
