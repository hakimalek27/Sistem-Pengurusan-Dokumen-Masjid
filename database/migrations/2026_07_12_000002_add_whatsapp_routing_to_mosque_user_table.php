<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mosque_user', function (Blueprint $table) {
            $table->string('phone_wa', 20)->nullable()->after('role');
            $table->boolean('notify_whatsapp')->default(true)->after('phone_wa');
            $table->unique(['mosque_id', 'phone_wa'], 'mosque_user_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('mosque_user', function (Blueprint $table) {
            $table->dropUnique('mosque_user_phone_unique');
            $table->dropColumn(['phone_wa', 'notify_whatsapp']);
        });
    }
};
