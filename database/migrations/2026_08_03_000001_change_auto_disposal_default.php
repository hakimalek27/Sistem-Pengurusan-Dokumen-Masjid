<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADDENDUM v2.6 — lalai pelupusan automatik DIMATIKAN untuk masjid BAHARU.
 *
 * Pelaksanaan F4 (L2), `PELAN-PEMBAIKAN.md` §5.3. Menutup RR-08-01/RR-09-01: auto-padam
 * ialah tingkah laku LALAI melalui tiga lapisan bertindan. Ini lapisan kedua — suis
 * per-masjid.
 *
 * ⚠️ HANYA default kolum yang berubah. `->change()` TIDAK menyentuh baris yang sudah ada,
 * jadi masjid sedia ada (mamad, smoke, demo) kekal dengan nilai masing-masing. Menukar
 * masjid sedia ada ialah keputusan operasi berasingan (arahan tinker terdokumen), bukan
 * migrasi skema — jangan ubah data operasi dalam migrasi.
 *
 * Teks pengakuan §16.2 pada `/daftar` dikemas dalam commit yang SAMA: pengakuan yang
 * ditandatangani masjid baharu mesti menerangkan tingkah laku sistem yang sebenar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            $table->boolean('auto_disposal_enabled')->default(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            $table->boolean('auto_disposal_enabled')->default(true)->change();
        });
    }
};
