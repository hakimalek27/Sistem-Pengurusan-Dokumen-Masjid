<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.1 mosques (TENANT)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mosques', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();            // URL tenant; a-z0-9
            $table->string('code', 6)->unique();         // akronim penomboran (MAM) — kunci selepas ada fail
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('menunggu'); // menunggu/aktif/digantung/ditutup
            $table->bigInteger('storage_quota_bytes')->default(21474836480); // lalai 20GB (base)
            $table->bigInteger('storage_used_bytes')->default(0);            // kaunter cache (§5.14)
            $table->boolean('auto_disposal_enabled')->default(true);         // suis §2.2/§16
            $table->timestamp('retention_ack_at')->nullable();
            $table->foreignId('retention_ack_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('wa_session_id')->nullable()->unique(); // ID sesi gateway (§11.1); NULL = WA tak aktif
            $table->string('wa_number', 20)->nullable();           // nombor WA rasmi (paparan)
            $table->jsonb('settings')->default('{}');   // data_protection_rep, wa_intake_enabled, wa_intake_keyword
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes(); // tutup akaun §10.M
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mosques');
    }
};
