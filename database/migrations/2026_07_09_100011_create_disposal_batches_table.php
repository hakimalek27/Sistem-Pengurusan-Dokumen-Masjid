<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.12 disposal_batches (+mosque_id, kind manual|auto)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->string('kind'); // manual | auto
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draf'); // draf/menunggu_kelulusan/lulus/selesai/dibatalkan
            $table->timestamp('executed_at')->nullable();
            $table->string('certificate_path')->nullable();
            $table->timestamps();
            $table->index(['mosque_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_batches');
    }
};
