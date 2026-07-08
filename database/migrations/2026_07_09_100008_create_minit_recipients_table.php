<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.9 minit_recipients
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minit_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('minit_id')->constrained('minits')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('jenis'); // tindakan | makluman
            $table->timestamp('read_at')->nullable();
            $table->string('status')->default('belum'); // belum/dibaca/selesai
            $table->timestamps();
            $table->unique(['minit_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minit_recipients');
    }
};
