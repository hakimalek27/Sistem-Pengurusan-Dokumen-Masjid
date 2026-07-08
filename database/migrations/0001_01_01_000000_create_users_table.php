<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // users — GLOBAL (tiada mosque_id) §5.3
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();          // magic link = laluan utama; kata laluan = fallback
            $table->boolean('is_superadmin')->default(false); // Gate::before lulus semua + akses semua tenant (§6.0)
            $table->string('phone_wa', 20)->nullable()->unique(); // E.164 tanpa '+' (padanan webhook global)
            $table->string('telegram_chat_id')->nullable();
            $table->string('jawatan')->nullable();
            $table->boolean('notify_whatsapp')->default(true);
            $table->boolean('notify_telegram')->default(false);
            $table->boolean('notify_email')->default(true);
            $table->boolean('is_active')->default(true);      // nyahaktif ≠ padam; TIADA butang padam
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
