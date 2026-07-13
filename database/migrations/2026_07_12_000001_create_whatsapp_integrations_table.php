<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->unique()->constrained('mosques')->cascadeOnDelete();
            $table->string('external_id', 120)->unique();
            $table->string('gateway_tenant_id')->nullable()->unique();
            $table->text('api_key')->nullable();
            $table->string('api_key_prefix', 20)->nullable();
            $table->boolean('enabled')->default(false);
            $table->string('status', 30)->default('unlinked');
            $table->string('session_id')->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_integrations');
    }
};
