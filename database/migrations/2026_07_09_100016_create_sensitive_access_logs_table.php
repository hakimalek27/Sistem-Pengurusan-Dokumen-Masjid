<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.16 sensitive_access_logs (+mosque_id, +is_superadmin)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sensitive_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->boolean('is_superadmin')->default(false);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('record_id')->nullable()->constrained('records')->nullOnDelete();
            $table->string('action'); // view | download
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable(); // created_at sahaja
            $table->index(['mosque_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sensitive_access_logs');
    }
};
