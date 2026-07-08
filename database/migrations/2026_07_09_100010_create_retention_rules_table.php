<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.11 retention_rules (mosque_id NULLABLE — NULL = lalai platform)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->nullable()->constrained('mosques')->cascadeOnDelete(); // NULL = lalai platform
            $table->string('record_type')->nullable();
            $table->string('classification_prefix')->nullable(); // cth '200'
            $table->integer('retain_years')->nullable();         // NULL = kekal
            $table->string('action'); // kekal | semak | auto_padam
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['mosque_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_rules');
    }
};
