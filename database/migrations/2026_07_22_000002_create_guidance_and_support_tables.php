<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guidance_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mosque_id')->nullable()->constrained('mosques')->cascadeOnDelete();
            $table->string('context_key', 80);
            $table->string('mode', 20)->default('lengkap');
            $table->boolean('auto_start_enabled')->default(true);
            $table->boolean('nudges_enabled')->default(true);
            $table->boolean('digest_email')->default(false);
            $table->boolean('digest_whatsapp')->default(false);
            $table->boolean('digest_telegram')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'context_key']);
            $table->index(['mosque_id', 'mode']);
        });

        Schema::create('guidance_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mosque_id')->nullable()->constrained('mosques')->cascadeOnDelete();
            $table->string('context_key', 80);
            $table->string('guide_id', 190);
            $table->unsignedInteger('guide_version')->default(1);
            $table->unsignedInteger('step_index')->default(0);
            $table->string('status', 20)->default('belum_mula');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('dismissed_until')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'context_key', 'guide_id']);
            $table->index(['mosque_id', 'status']);
        });

        Schema::create('help_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mosque_id')->nullable()->constrained('mosques')->cascadeOnDelete();
            $table->string('panel', 20);
            $table->string('guide_id', 190)->nullable();
            $table->string('event', 50);
            $table->unsignedInteger('result_count')->nullable();
            $table->char('query_hash', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['mosque_id', 'created_at']);
            $table->index(['event', 'created_at']);
            $table->index(['query_hash', 'created_at']);
        });

        Schema::create('help_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->nullable()->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('panel', 20)->default('all');
            $table->string('title');
            $table->text('body');
            $table->json('roles')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['mosque_id', 'is_active', 'starts_at']);
        });

        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('mosque_id')->nullable()->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_session_hash', 64)->nullable();
            $table->string('panel', 20);
            $table->string('role', 40)->nullable();
            $table->string('category', 50);
            $table->string('subject', 180);
            $table->text('expected');
            $table->text('actual');
            $table->string('route_template', 255)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->json('browser_context')->nullable();
            $table->text('unmatched_query')->nullable();
            $table->boolean('query_consent')->default(false);
            $table->string('status', 20)->default('baharu');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['mosque_id', 'status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['reporter_session_hash', 'created_at']);
            $table->index(['request_id']);
        });

        Schema::create('support_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_request_id')->constrained('support_requests')->cascadeOnDelete();
            $table->foreignId('mosque_id')->nullable()->constrained('mosques')->cascadeOnDelete();
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('scan_status', 20);
            $table->string('scan_signature')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['mosque_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_attachments');
        Schema::dropIfExists('support_requests');
        Schema::dropIfExists('help_announcements');
        Schema::dropIfExists('help_events');
        Schema::dropIfExists('guidance_progress');
        Schema::dropIfExists('guidance_preferences');
    }
};
