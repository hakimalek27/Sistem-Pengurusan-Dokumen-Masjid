<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.5 classification_nodes (+mosque_id)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classification_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('classification_nodes')->nullOnDelete();
            $table->string('level'); // fungsi | aktiviti | sub_aktiviti
            $table->string('code');  // fungsi 500; aktiviti 500-1; sub 500-1/2
            $table->string('title');
            $table->string('default_sensitivity')->default('dalaman');
            $table->boolean('is_active')->default(true);
            $table->integer('sort')->default(0);
            $table->timestamps();
            $table->unique(['mosque_id', 'parent_id', 'code']);
            $table->index(['mosque_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classification_nodes');
    }
};
