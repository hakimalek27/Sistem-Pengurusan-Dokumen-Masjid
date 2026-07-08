<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.16 file_access_grants — akses khas fail sulit kepada individu luar peranan lalai
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registry_file_id')->constrained('registry_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['registry_file_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_access_grants');
    }
};
