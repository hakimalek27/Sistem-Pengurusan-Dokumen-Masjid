<?php

use App\Models\DisposalBatch;
use App\Models\SensitiveAccessLog;
use App\Models\StoredExport;
use App\Services\BillingService;
use App\Services\SecureDownloadUrl;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->mosque = makeMosque('MAM', 'mam');
    $this->admin = makeMember($this->mosque, 'admin_masjid');
});

it('pratonton dan muat turun media menggunakan URL signed 5 minit serta log sulit', function () {
    $record = makeRecord($this->mosque, makeFile($this->mosque, makeNode($this->mosque, '200-2', 'sulit'), 'sulit'), 'sulit');
    $media = $record->addMediaFromString('%PDF-1.4 ujian')->usingFileName('sulit.pdf')->toMediaCollection('original');
    $media->update(['mime_type' => 'application/pdf']);
    $urls = app(SecureDownloadUrl::class);

    $this->actingAs($this->admin)
        ->get($urls->media($media, 'inline'))
        ->assertOk()
        ->assertHeader('content-disposition', 'inline; filename=sulit.pdf');

    $this->actingAs($this->admin)
        ->get($urls->media($media, 'attachment'))
        ->assertOk()
        ->assertDownload('sulit.pdf');

    expect(SensitiveAccessLog::query()->where('record_id', $record->id)->where('action', 'view')->count())->toBe(1)
        ->and(SensitiveAccessLog::query()->where('record_id', $record->id)->where('action', 'download')->count())->toBe(1);
});

it('menolak URL media tanpa signature dan pengguna tanpa akses sensitiviti', function () {
    $record = makeRecord($this->mosque, makeFile($this->mosque, makeNode($this->mosque, '800-1', 'sulit'), 'sulit'), 'sulit');
    $media = $record->addMediaFromString('%PDF-1.4 ujian')->usingFileName('sulit.pdf')->toMediaCollection('original');
    $media->update(['mime_type' => 'application/pdf']);
    $ajk = makeMember($this->mosque, 'ajk');

    $this->actingAs($this->admin)->get('/secure-file/'.$media->id)->assertForbidden();
    $this->actingAs($ajk)->get(app(SecureDownloadUrl::class)->media($media))->assertNotFound();
});

it('viewer dokumen memerlukan signature, akses tenant dan memaparkan metadata cetakan', function () {
    $record = makeRecord($this->mosque, makeFile($this->mosque, makeNode($this->mosque, '100-4')), 'dalaman', 'surat_menyurat', [
        'title' => 'Surat Untuk Viewer',
        'our_ref' => 'MAM/100/VIEWER-1',
    ]);
    $media = $record->addMediaFromString('%PDF-1.4 viewer')->usingFileName('viewer.pdf')->toMediaCollection('original');
    $media->update(['mime_type' => 'application/pdf']);
    $url = app(SecureDownloadUrl::class)->viewer($media);

    $this->actingAs($this->admin)
        ->get($url)
        ->assertOk()
        ->assertSee('Kawalan viewer dokumen')
        ->assertSee('Surat Untuk Viewer')
        ->assertSee('MAM/100/VIEWER-1')
        ->assertSee('Halaman')
        ->assertSee('Cari teks')
        ->assertSee('Cetak Metadata');

    $outsider = makeMember(makeMosque('MAN', 'man'), 'admin_masjid');
    $this->actingAs($outsider)->get($url)->assertNotFound();
});

it('deep-link mendarat terus di halaman rekod dan menyembunyikan rekod sulit', function () {
    $record = makeRecord($this->mosque, makeFile($this->mosque, makeNode($this->mosque, '800-1', 'sulit'), 'sulit'), 'sulit');
    $ajk = makeMember($this->mosque, 'ajk');

    $this->actingAs($this->admin)->get('/r/'.$record->ulid)->assertRedirect('/app/mam/records/'.$record->id);
    $this->actingAs($ajk)->get('/r/'.$record->ulid)->assertNotFound();
});

it('menyediakan invois dan sijil melalui route signed dengan authorization', function () {
    $order = app(BillingService::class)->createOrder($this->mosque, $this->admin, 1);
    $batch = DisposalBatch::query()->create([
        'mosque_id' => $this->mosque->id,
        'kind' => 'manual',
        'created_by' => $this->admin->id,
        'status' => 'selesai',
        'executed_at' => now(),
        'certificate_path' => 'tenants/'.$this->mosque->id.'/disposal-certs/1.pdf',
    ]);
    Storage::disk(config('diwan.storage_disk'))->put($batch->certificate_path, '%PDF sijil');
    $audit = makeMember($this->mosque, 'audit');
    $outsider = makeMember(makeMosque('MAN', 'man'), 'admin_masjid');
    $urls = app(SecureDownloadUrl::class);

    $this->actingAs($this->admin)->get($urls->invoice($order))->assertOk()->assertDownload($order->invoice_no.'.pdf');
    $this->actingAs($audit)->get($urls->certificate($batch))->assertOk()->assertDownload('sijil-pelupusan-'.$batch->id.'.pdf');
    $this->actingAs($outsider)->get($urls->invoice($order))->assertNotFound();
});

it('mengehadkan eksport kepada pemilik atau pegawai dibenarkan dan tarikh luput', function () {
    $path = 'tenants/'.$this->mosque->id.'/exports/ujian.zip';
    Storage::disk(config('diwan.storage_disk'))->put($path, 'zip');
    $export = StoredExport::query()->create([
        'mosque_id' => $this->mosque->id,
        'requested_by' => $this->admin->id,
        'label' => 'retensi-ujian',
        'path' => $path,
        'expires_at' => now()->addDays(14),
    ]);

    $this->actingAs($this->admin)
        ->get(app(SecureDownloadUrl::class)->export($export))
        ->assertOk()
        ->assertDownload('retensi-ujian.zip');

    $export->update(['expires_at' => now()->subMinute()]);
    $futureSigned = URL::temporarySignedRoute('secure-artifact.export', now()->addMinute(), ['export' => $export->id]);
    $this->actingAs($this->admin)->get($futureSigned)->assertNotFound();
});
