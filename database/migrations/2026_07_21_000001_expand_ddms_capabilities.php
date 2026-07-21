<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('criteria');
            $table->boolean('is_default')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['mosque_id', 'user_id', 'name']);
        });

        Schema::create('favourites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type', 30);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();
            $table->unique(['mosque_id', 'user_id', 'target_type', 'target_id']);
            $table->index(['mosque_id', 'target_type', 'target_id']);
        });

        Schema::create('record_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('record_id')->constrained('records')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->json('proposed_changes');
            $table->string('status', 20)->default('menunggu');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['mosque_id', 'status']);
        });

        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('principal_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delegate_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('capabilities');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['mosque_id', 'delegate_user_id', 'is_active'], 'delegations_delegate_active_index');
        });

        Schema::create('file_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->foreignId('registry_file_id')->constrained('registry_files')->cascadeOnDelete();
            $table->string('action', 30);
            $table->string('from_location')->nullable();
            $table->string('to_location')->nullable();
            $table->foreignId('holder_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('holder_name')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('handled_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['mosque_id', 'registry_file_id', 'created_at'], 'file_movements_tenant_file_created_index');
        });

        Schema::table('records', function (Blueprint $table) {
            $table->string('virus_scan_status', 20)->default('tidak_diimbas')->after('source_meta');
            $table->string('virus_signature')->nullable()->after('virus_scan_status');
            $table->timestamp('virus_scanned_at')->nullable()->after('virus_signature');
        });

        Schema::table('registry_files', function (Blueprint $table) {
            $table->string('medium', 20)->default('elektronik')->after('title');
            $table->string('physical_reference')->nullable()->after('medium');
            $table->string('physical_location')->nullable()->after('physical_reference');
            $table->string('custody_status', 20)->default('dalam_simpanan')->after('physical_location');
            $table->foreignId('current_holder_user_id')->nullable()->after('custody_status')->constrained('users')->nullOnDelete();
            $table->string('current_holder_name')->nullable()->after('current_holder_user_id');
            $table->timestamp('custody_due_at')->nullable()->after('current_holder_name');
        });

        Schema::table('approvals', function (Blueprint $table) {
            $table->foreignId('decided_by')->nullable()->after('approver_id')->constrained('users')->nullOnDelete();
            $table->foreignId('on_behalf_of')->nullable()->after('decided_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('minit_recipients', function (Blueprint $table) {
            $table->foreignId('acted_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('acted_on_behalf_of_user_id')->nullable()->after('acted_by_user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('minit_recipients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('acted_on_behalf_of_user_id');
            $table->dropConstrainedForeignId('acted_by_user_id');
        });
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('on_behalf_of');
            $table->dropConstrainedForeignId('decided_by');
        });
        Schema::table('registry_files', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_holder_user_id');
            $table->dropColumn(['medium', 'physical_reference', 'physical_location', 'custody_status', 'current_holder_name', 'custody_due_at']);
        });
        Schema::table('records', function (Blueprint $table) {
            $table->dropColumn(['virus_scan_status', 'virus_signature', 'virus_scanned_at']);
        });

        Schema::dropIfExists('file_movements');
        Schema::dropIfExists('delegations');
        Schema::dropIfExists('record_correction_requests');
        Schema::dropIfExists('favourites');
        Schema::dropIfExists('saved_searches');
    }
};
