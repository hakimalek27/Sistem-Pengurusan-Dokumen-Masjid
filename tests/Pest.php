<?php

use App\Enums\MosqueStatus;
use App\Enums\OcrStatus;
use App\Enums\RecordStatus;
use App\Enums\SourceChannel;
use App\Models\ClassificationNode;
use App\Models\Mosque;
use App\Models\Record;
use App\Models\RegistryFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Pembantu Kilang (Test Factory Helpers) — dikongsi semua ujian
|--------------------------------------------------------------------------
*/

function makeMosque(string $code, string $slug, MosqueStatus $status = MosqueStatus::Aktif): Mosque
{
    return Mosque::query()->create([
        'name' => "Masjid {$code}",
        'slug' => $slug,
        'code' => $code,
        'status' => $status,
        'wa_session_id' => $slug,
        'storage_quota_bytes' => 20 * (1024 ** 3),
        'settings' => ['wa_intake_enabled' => true, 'wa_intake_keyword' => 'spdm'],
    ]);
}

function makeNode(Mosque $mosque, string $code, string $sensitivity = 'dalaman', string $level = 'aktiviti'): ClassificationNode
{
    return ClassificationNode::query()->create([
        'mosque_id' => $mosque->id,
        'level' => $level,
        'code' => $code,
        'title' => "Nod {$code}",
        'default_sensitivity' => $sensitivity,
        'is_active' => true,
    ]);
}

function makeMember(Mosque $mosque, string $role, ?string $email = null, array $attrs = []): User
{
    $user = User::query()->create(array_merge([
        'name' => ucwords(str_replace('_', ' ', $role)),
        'email' => $email ?? $role.'-'.$mosque->slug.'-'.uniqid().'@ujian.test',
        'is_active' => true,
    ], $attrs));

    $mosque->users()->attach($user->id, ['role' => $role, 'joined_at' => now()]);

    return $user;
}

function makeFile(Mosque $mosque, ClassificationNode $node, string $sensitivity = 'dalaman'): RegistryFile
{
    $txn = ((int) RegistryFile::query()->withoutGlobalScope('mosque')
        ->where('mosque_id', $mosque->id)
        ->where('classification_node_id', $node->id)
        ->max('transaction_no')) + 1;

    return RegistryFile::query()->create([
        'mosque_id' => $mosque->id,
        'classification_node_id' => $node->id,
        'transaction_no' => $txn,
        'volume' => 1,
        'file_no' => "{$mosque->code}.{$node->code}/{$txn}",
        'title' => "Fail {$node->code}",
        'sensitivity' => $sensitivity,
        'status' => 'terbuka',
        'enclosure_count' => 0,
        'opened_at' => now(),
    ]);
}

function makeRecord(Mosque $mosque, ?RegistryFile $file, string $sensitivity = 'dalaman', string $type = 'surat_menyurat', array $attrs = []): Record
{
    return Record::query()->create(array_merge([
        'mosque_id' => $mosque->id,
        'registry_file_id' => $file?->id,
        'record_type' => $type,
        'title' => 'Rekod ujian',
        'record_date' => now(),
        'sensitivity' => $sensitivity,
        'status' => $file ? RecordStatus::Difailkan : RecordStatus::PetiMasuk,
        'ocr_status' => OcrStatus::Belum,
        'source_channel' => SourceChannel::MuatNaik,
    ], $attrs));
}
