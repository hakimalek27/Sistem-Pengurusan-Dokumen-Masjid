<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mosque_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 40)->nullable();
            $table->string('action', 60);
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('record_title')->nullable();
            $table->string('record_reference')->nullable();
            $table->unsignedBigInteger('registry_file_id')->nullable();
            $table->string('file_no')->nullable();
            $table->string('file_title')->nullable();
            $table->string('source_channel', 30)->nullable();
            $table->string('source_identifier')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['mosque_id', 'created_at']);
            $table->index(['mosque_id', 'action', 'created_at']);
            $table->index(['mosque_id', 'actor_id', 'created_at']);
            $table->index(['mosque_id', 'record_id', 'created_at']);
            $table->index(['mosque_id', 'registry_file_id', 'created_at'], 'mosque_activity_file_created_index');
            $table->index(['mosque_id', 'source_channel', 'created_at'], 'mosque_activity_source_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosque_activity_logs');
    }
};
