<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Gabungkan rekod kerani lama ke role kanonik Admin / Kerani. */
    public function up(): void
    {
        DB::table('mosque_user')
            ->where('role', 'kerani')
            ->update(['role' => 'admin_masjid']);
    }

    public function down(): void
    {
        // Tidak boleh memulihkan pemisahan role tanpa sejarah role asal.
    }
};
