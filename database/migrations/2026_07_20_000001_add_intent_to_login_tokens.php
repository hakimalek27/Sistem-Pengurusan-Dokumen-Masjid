<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §15.1 — Magic link deep-link: pautan notifikasi (minit/kelulusan) membawa
 * destinasi (intended_url) supaya penerima auto-login terus ke rekod. purpose
 * membezakan pautan log masuk biasa (15 min) daripada pautan notifikasi (72 jam).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_tokens', function (Blueprint $table) {
            $table->string('intended_url', 500)->nullable()->after('email');
            $table->string('purpose', 20)->default('login')->after('intended_url');
        });
    }

    public function down(): void
    {
        Schema::table('login_tokens', function (Blueprint $table) {
            $table->dropColumn(['intended_url', 'purpose']);
        });
    }
};
