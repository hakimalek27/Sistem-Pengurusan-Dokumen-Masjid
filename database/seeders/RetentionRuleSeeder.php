<?php

namespace Database\Seeders;

use App\Models\RetentionRule;
use Illuminate\Database\Seeder;

// §16.1 — Peraturan retensi lalai PLATFORM (mosque_id = NULL)
class RetentionRuleSeeder extends Seeder
{
    public function run(): void
    {
        $years = (int) config('diwan.default_retention_years', 7);

        // Kekal (injap §2.2) — retain_years NULL, action kekal.
        $kekal = ['minit_mesyuarat', 'perjanjian', 'sijil', 'laporan'];

        // Auto padam 7 tahun (jenis rekod).
        $autoPadam = [
            'surat_menyurat', 'memo', 'emel', 'emel_muatnaik', 'borang', 'kertas_kerja',
            'pekeliling', 'garis_panduan', 'jadual', 'poster', 'foto',
            'tender_sebutharga', 'rekod_kewangan',
        ];

        foreach ($kekal as $type) {
            RetentionRule::query()->updateOrCreate(
                ['mosque_id' => null, 'record_type' => $type, 'classification_prefix' => null],
                ['retain_years' => null, 'action' => 'kekal', 'note' => 'Lalai platform: kekal (§16.1)'],
            );
        }

        foreach ($autoPadam as $type) {
            RetentionRule::query()->updateOrCreate(
                ['mosque_id' => null, 'record_type' => $type, 'classification_prefix' => null],
                ['retain_years' => $years, 'action' => 'auto_padam', 'note' => 'Lalai platform: 7 tahun (§16.1)'],
            );
        }

        // Prefix klasifikasi 200 (Kewangan) — 7 tahun auto padam.
        RetentionRule::query()->updateOrCreate(
            ['mosque_id' => null, 'record_type' => null, 'classification_prefix' => '200'],
            ['retain_years' => $years, 'action' => 'auto_padam', 'note' => 'Lalai platform: Kewangan 200 (§16.1)'],
        );
    }
}
