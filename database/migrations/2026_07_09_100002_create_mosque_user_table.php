<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.2 mosque_user (pivot keahlian + PERANAN)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mosque_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role'); // §6.1
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['mosque_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosque_user');
    }
};
