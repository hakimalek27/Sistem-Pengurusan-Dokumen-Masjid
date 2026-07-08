<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.8 minits (+mosque_id)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('record_id')->constrained('records')->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('priority')->default('biasa'); // biasa/segera/kritikal
            $table->date('due_at')->nullable();
            $table->string('status')->default('terbuka'); // terbuka/selesai
            $table->foreignId('parent_id')->nullable()->constrained('minits')->nullOnDelete(); // bebenang
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['mosque_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minits');
    }
};
