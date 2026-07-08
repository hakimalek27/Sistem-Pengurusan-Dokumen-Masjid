<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.12 disposal_items — metadata_snapshot KEKAL selamanya
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('disposal_batches')->cascadeOnDelete();
            $table->foreignId('record_id')->constrained('records')->cascadeOnDelete();
            $table->jsonb('metadata_snapshot'); // salinan PENUH metadata + lampiran + file_no saat pelupusan
            $table->timestamps();
            $table->index(['batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_items');
    }
};
