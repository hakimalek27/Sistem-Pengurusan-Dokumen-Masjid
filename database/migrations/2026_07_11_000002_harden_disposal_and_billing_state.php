<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposal_batches', function (Blueprint $table) {
            $table->timestamp('execution_started_at')->nullable()->after('status');
            $table->text('failure_reason')->nullable()->after('certificate_path');
        });

        Schema::table('disposal_items', function (Blueprint $table) {
            $table->string('state')->default('pending')->after('metadata_snapshot');
            $table->text('error')->nullable()->after('state');
            $table->timestamp('finalized_at')->nullable()->after('error');
            $table->unique(['batch_id', 'record_id']);
        });

        Schema::table('storage_addons', function (Blueprint $table) {
            $table->unique('storage_order_id');
        });

        Schema::table('storage_orders', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable()->unique()->after('invoice_no');
        });
    }

    public function down(): void
    {
        Schema::table('storage_orders', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });

        Schema::table('storage_addons', function (Blueprint $table) {
            $table->dropUnique(['storage_order_id']);
        });

        Schema::table('disposal_items', function (Blueprint $table) {
            $table->dropUnique(['batch_id', 'record_id']);
            $table->dropColumn(['state', 'error', 'finalized_at']);
        });

        Schema::table('disposal_batches', function (Blueprint $table) {
            $table->dropColumn(['execution_started_at', 'failure_reason']);
        });
    }
};
