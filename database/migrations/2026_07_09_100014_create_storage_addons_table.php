<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.13 storage_addons — Kuota efektif = storage_quota_bytes + Σ(addon aktif)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('storage_order_id')->nullable()->constrained('storage_orders')->nullOnDelete();
            $table->integer('gb');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // NULL = kekal
            $table->string('status')->default('aktif');  // aktif | luput
            $table->timestamps();
            $table->index(['mosque_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_addons');
    }
};
