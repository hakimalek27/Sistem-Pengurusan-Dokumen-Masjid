<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// §5.7 records (TERAS; +mosque_id index)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mosque_id')->constrained('mosques')->cascadeOnDelete();
            $table->ulid('ulid')->unique();                 // URL deep-link
            $table->foreignId('registry_file_id')->nullable()->index()->constrained('registry_files')->nullOnDelete(); // NULL = Peti Masuk
            $table->string('record_type', 50)->index();     // kunci config/record_types.php
            $table->string('title')->nullable();            // NULL sah di peti masuk
            $table->string('our_ref')->nullable();
            $table->string('their_ref')->nullable();
            $table->date('record_date')->nullable();
            $table->date('received_date')->nullable();
            $table->string('direction')->nullable();        // masuk / keluar / dalaman
            $table->string('sender_name')->nullable();
            $table->string('sender_org')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('sensitivity')->default('dalaman')->index();
            $table->string('status')->default('peti_masuk')->index();
            $table->integer('enclosure_no')->nullable();    // no. kandungan dalam fail
            $table->jsonb('metadata')->default('{}');       // medan khusus jenis §8
            $table->string('ocr_status')->default('belum'); // belum/dalam_proses/siap/gagal
            $table->text('ocr_text')->nullable();           // had 1,000,000 aksara (di kod)
            $table->char('sha256', 64)->nullable()->index();
            $table->string('source_channel');               // muat_naik/emel/whatsapp/imbasan
            $table->jsonb('source_meta')->default('{}');    // {"from":..,"subject":..}
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('filed_at')->nullable();
            $table->foreignId('superseded_by_record_id')->nullable()->constrained('records')->nullOnDelete(); // ganti versi
            $table->boolean('legal_hold')->default(false);  // §16 — dipegang = tak dipadam
            $table->date('retention_due_at')->nullable();   // dikira enjin §16 (NULL = kekal/hold)
            $table->jsonb('retention_notified')->default('{}'); // {"t90":ts,"t30":ts,"t7":ts}
            $table->timestamps();
            $table->softDeletes();

            $table->index(['mosque_id', 'status']);
            $table->index(['mosque_id', 'retention_due_at']);
            $table->index(['registry_file_id', 'enclosure_no']);
            $table->index(['record_type', 'record_date']);
            $table->index(['mosque_id', 'sha256']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
