<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * §4.6′ — Pemetaan folder/fail Google Drive per entiti (id folder disimpan supaya
 * resolusi SENTIASA melalui id induk yang diketahui — jaminan isolasi tenant).
 * records.gdrive_meta = peta lampiran {media_id: drive_id} + sha256 + nama/folder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mosques', function (Blueprint $table) {
            $table->string('gdrive_folder_id')->nullable()->after('wa_number');
        });

        Schema::table('classification_nodes', function (Blueprint $table) {
            $table->string('gdrive_folder_id')->nullable();
        });

        Schema::table('registry_files', function (Blueprint $table) {
            $table->string('gdrive_folder_id')->nullable();
        });

        Schema::table('records', function (Blueprint $table) {
            $table->string('gdrive_file_id')->nullable()->index();
            $table->json('gdrive_meta')->nullable();
            $table->timestamp('gdrive_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mosques', fn (Blueprint $t) => $t->dropColumn('gdrive_folder_id'));
        Schema::table('classification_nodes', fn (Blueprint $t) => $t->dropColumn('gdrive_folder_id'));
        Schema::table('registry_files', fn (Blueprint $t) => $t->dropColumn('gdrive_folder_id'));
        Schema::table('records', fn (Blueprint $t) => $t->dropColumn(['gdrive_file_id', 'gdrive_meta', 'gdrive_synced_at']));
    }
};
