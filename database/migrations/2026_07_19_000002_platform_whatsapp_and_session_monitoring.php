<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fasa D — integrasi WhatsApp peringkat platform (mosque_id null) untuk alert
// superadmin, + lajur pemantauan sesi (alert nyah-sambung dgn cooldown).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_integrations', function (Blueprint $table) {
            // Integrasi platform tidak terikat mana-mana masjid (dikenal pasti
            // oleh external_id "{instance}:platform"). Unik pada mosque_id kekal;
            // berbilang NULL dibenarkan dlm SQLite & PostgreSQL, tetapi hanya satu
            // baris platform wujud (dikuatkuasa melalui external_id unik + kod).
            $table->unsignedBigInteger('mosque_id')->nullable()->change();

            $table->string('last_alert_status', 30)->nullable()->after('last_error');
            $table->timestamp('last_alerted_at')->nullable()->after('last_alert_status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_integrations', function (Blueprint $table) {
            $table->dropColumn(['last_alert_status', 'last_alerted_at']);
            // mosque_id dibiar nullable — memulihkan NOT NULL boleh gagal jika
            // wujud baris platform.
        });
    }
};
