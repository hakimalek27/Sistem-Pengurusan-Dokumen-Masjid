<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fasa B — log masuk telefon-atau-e-mel: e-mel jadi PILIHAN (nullable),
// magic link login_tokens boleh dikunci pada user_id (bukan hanya e-mel).
return new class extends Migration
{
    public function up(): void
    {
        // §5.3 — e-mel kini pilihan (ahli boleh guna telefon sahaja). Unik kekal;
        // berbilang NULL dibenarkan pada indeks unik dlm SQLite dan PostgreSQL.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // §5.4 — token boleh merujuk user secara langsung (telefon-sahaja tiada e-mel).
        Schema::table('login_tokens', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('login_tokens', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('login_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        // Nota: 'email' pada users/login_tokens dibiarkan nullable — memulihkan
        // NOT NULL boleh gagal jika wujud baris telefon-sahaja (email = NULL).
    }
};
