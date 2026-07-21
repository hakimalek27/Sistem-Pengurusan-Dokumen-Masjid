<?php

namespace Database\Seeders;

use App\Enums\MosqueStatus;
use App\Enums\OcrStatus;
use App\Enums\RecordStatus;
use App\Enums\SourceChannel;
use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * §17 langkah 5 — Data demo (local/testing SAHAJA). Kata laluan demo: `password`.
 * 2 masjid MAM & MAN (aktif, KF tersalin, wa_session_id mam/man) + superadmin +
 * pengguna semua peranan di MAM + 1 pengguna dwi-masjid + fail & rekod (termasuk backdate ~7 tahun).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Superadmin platform.
        User::query()->updateOrCreate(
            ['email' => 'superadmin@diwan.test'],
            [
                'name' => 'Superadmin Diwan',
                'password' => Hash::make('password'),
                'is_superadmin' => true,
                'is_active' => true,
                'phone_wa' => '60100000000',
            ],
        );

        $mam = $this->makeMosque('Masjid Al-Muttaqin Wangsa Melawati', 'mam', 'MAM', 'mam', 'W.P. Kuala Lumpur');
        $man = $this->makeMosque('Masjid An-Nur Demo', 'man', 'MAN', 'man', 'Selangor');

        $this->copyKf($mam);
        $this->copyKf($man);

        // Pengguna semua peranan di MAM (§6.1); admin_masjid merangkumi kerani.
        $i = 1;
        foreach (config('roles.list', []) as $role) {
            $user = User::query()->updateOrCreate(
                ['email' => "{$role}@demo.test"],
                [
                    'name' => ucwords(str_replace('_', ' ', $role)).' (MAM)',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'phone_wa' => '6011000000'.$i,
                    'jawatan' => Roles::label($role),
                ],
            );
            $mam->users()->syncWithoutDetaching([$user->id => ['role' => $role, 'joined_at' => now()]]);
            $i++;
        }

        // Admin untuk MAN.
        $manAdmin = User::query()->updateOrCreate(
            ['email' => 'admin@man.test'],
            ['name' => 'Pentadbir MAN', 'password' => Hash::make('password'), 'is_active' => true, 'phone_wa' => '60122000001'],
        );
        $man->users()->syncWithoutDetaching([$manAdmin->id => ['role' => 'admin_masjid', 'joined_at' => now()]]);

        // Pengguna dwi-masjid: ajk di MAM + Admin / Kerani di MAN (§18.3 ujian isolasi).
        $dwi = User::query()->updateOrCreate(
            ['email' => 'dwi@demo.test'],
            ['name' => 'Ahli Dwi-Masjid', 'password' => Hash::make('password'), 'is_active' => true, 'phone_wa' => '60133000001'],
        );
        $mam->users()->syncWithoutDetaching([$dwi->id => ['role' => 'ajk', 'joined_at' => now()]]);
        $man->users()->syncWithoutDetaching([$dwi->id => ['role' => 'admin_masjid', 'joined_at' => now()]]);

        // Fail & rekod contoh (idempotent — hanya jika belum ada).
        if (! $mam->registryFiles()->exists()) {
            $kerani = User::query()->where('email', 'admin_masjid@demo.test')->first();

            $f1 = $this->makeFile($mam, '100-4', 'Surat-Menyurat Am 2026', $kerani);
            $f2 = $this->makeFile($mam, '200-2', 'Resit & Baucar 2026', $kerani);
            $f3 = $this->makeFile($mam, '500-1', 'Tempahan Dewan 2019', $kerani);

            $this->makeRecord($mam, $f1, 'surat_menyurat', 'Surat jemputan Mesyuarat AJK Bil 3/2026', $kerani);
            $this->makeRecord($mam, $f2, 'rekod_kewangan', 'Resit derma jariah Julai 2026', $kerani, [
                'metadata' => ['jenis_dokumen' => 'resit', 'amaun' => 500, 'pihak' => 'Orang Ramai'],
            ]);
            // Rekod backdate ~7 tahun (ujian retensi §18.34).
            $this->makeRecord($mam, $f3, 'surat_menyurat', 'Permohonan guna dewan serbaguna (2019)', $kerani, [
                'record_date' => now()->subYears(7)->subMonth(),
                'filed_at' => now()->subYears(7)->subMonth(),
            ]);

            // Rekod peti masuk (belum difailkan).
            Record::query()->create([
                'mosque_id' => $mam->id,
                'record_type' => 'surat_menyurat',
                'title' => 'Dokumen baharu dalam peti masuk',
                'record_date' => now(),
                'sensitivity' => 'dalaman',
                'status' => RecordStatus::PetiMasuk,
                'ocr_status' => OcrStatus::Belum,
                'source_channel' => SourceChannel::MuatNaik,
                'created_by' => $kerani?->id,
            ]);
        }

        if (! $man->registryFiles()->exists()) {
            $f = $this->makeFile($man, '900-1', 'Surat Majlis Agama Selangor', $manAdmin);
            $this->makeRecord($man, $f, 'surat_menyurat', 'Surat pekeliling MAIS 2026', $manAdmin);
        }
    }

    protected function makeMosque(string $name, string $slug, string $code, string $session, string $state): Mosque
    {
        return Mosque::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'code' => $code,
                'state' => $state,
                'status' => MosqueStatus::Aktif,
                'storage_quota_bytes' => (int) config('diwan.default_quota_gb', 20) * (1024 ** 3),
                'storage_used_bytes' => 0,
                'auto_disposal_enabled' => true,
                'wa_session_id' => $session,
                'wa_number' => '60312340000',
                'settings' => ['wa_intake_enabled' => true, 'wa_intake_keyword' => 'spdm'],
                'approved_at' => now(),
                'retention_ack_at' => now(),
            ],
        );
    }

    /** Salin templat KF §7 ke masjid (logik provisioning penuh = Fasa 2). */
    protected function copyKf(Mosque $mosque): void
    {
        if ($mosque->classificationNodes()->exists()) {
            return;
        }

        $template = require database_path('seeders/data/kf_template.php');

        foreach ($template as $code => [$title, $sensitivity, $activities]) {
            $fungsi = ClassificationNode::query()->create([
                'mosque_id' => $mosque->id,
                'parent_id' => null,
                'level' => 'fungsi',
                'code' => $code,
                'title' => $title,
                'default_sensitivity' => $sensitivity,
                'is_active' => true,
                'sort' => 0,
            ]);

            $sort = 0;
            foreach ($activities as $actCode => $actTitle) {
                ClassificationNode::query()->create([
                    'mosque_id' => $mosque->id,
                    'parent_id' => $fungsi->id,
                    'level' => 'aktiviti',
                    'code' => $actCode,
                    'title' => $actTitle,
                    'default_sensitivity' => $sensitivity,
                    'is_active' => true,
                    'sort' => $sort++,
                ]);
            }
        }
    }

    protected function makeFile(Mosque $mosque, string $nodeCode, string $title, ?User $by): RegistryFile
    {
        $node = $mosque->classificationNodes()->where('code', $nodeCode)->firstOrFail();
        $txn = ((int) $mosque->registryFiles()->where('classification_node_id', $node->id)->max('transaction_no')) + 1;

        return RegistryFile::query()->create([
            'mosque_id' => $mosque->id,
            'classification_node_id' => $node->id,
            'transaction_no' => $txn,
            'volume' => 1,
            'file_no' => "{$mosque->code}.{$node->code}/{$txn}",
            'title' => $title,
            'sensitivity' => $node->default_sensitivity,
            'status' => 'terbuka',
            'enclosure_count' => 0,
            'opened_at' => now(),
            'created_by' => $by?->id,
        ]);
    }

    protected function makeRecord(Mosque $mosque, RegistryFile $file, string $type, string $title, ?User $by, array $extra = []): Record
    {
        $enclosureNo = ((int) $file->records()->max('enclosure_no')) + 1;
        $file->increment('enclosure_count');

        return Record::query()->create(array_merge([
            'mosque_id' => $mosque->id,
            'registry_file_id' => $file->id,
            'record_type' => $type,
            'title' => $title,
            'record_date' => now(),
            'sensitivity' => $file->sensitivity,
            'status' => RecordStatus::Difailkan,
            'enclosure_no' => $enclosureNo,
            'ocr_status' => OcrStatus::Belum,
            'source_channel' => SourceChannel::MuatNaik,
            'created_by' => $by?->id,
            'filed_by' => $by?->id,
            'filed_at' => now(),
        ], $extra));
    }
}
