<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.16 notification_logs (+mosque_id nullable)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->nullable()->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('channel');
            $table->string('to')->nullable();
            $table->string('notification_type');
            $table->string('status'); // sent | failed
            $table->text('error')->nullable();
            $table->timestamp('created_at')->nullable(); // created_at sahaja
            $table->index(['mosque_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
