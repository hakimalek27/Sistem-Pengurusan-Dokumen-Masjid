<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('label');
            $table->string('path');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['mosque_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_exports');
    }
};
