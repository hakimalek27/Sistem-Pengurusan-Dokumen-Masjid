<?php

use App\Enums\SourceChannel;
use App\Filament\App\Resources\Inbox\Pages\ListInbox;
use App\Models\Record;
use App\Services\InboxIngestService;
use App\Services\QuotaService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * F5b (PELAN-PEMBAIKAN §6.2/§6.5) — matriks keadaan aliran muat naik Peti Masuk.
 *
 * Dua baris matriks (kuota penuh, paparan status antivirus) ditanda "Ya" oleh §6.5 tetapi
 * TIDAK sesuai sebagai e2e pelayar: kuota penuh memerlukan manipulasi DB, dan paparan lajur
 * badge lebih tepat diassert pada komponen. Kedua-duanya diuji di sini pada laluan kod
 * SEBENAR (`ListInbox` + `InboxTable`), bukan pada perkhidmatan sahaja.
 *
 * Baris matriks yang dijalankan dalam pelayar: `e2e/guidance-f5.spec.js`
 * (kewujudan sasaran DOM modal · fail sah → toast · format salah ditolak).
 */
beforeEach(function () {
    Storage::fake(config('diwan.storage_disk'));
    $this->mam = makeMosque('MAM', 'mam');
    $this->kerani = makeMember($this->mam, 'kerani');

    // Panel MESTI ditetapkan: tanpa ini Filament menyelesaikan URL sumber terhadap panel
    // `admin` dan render jadual gagal `Route [filament.admin.resources.peti-masuk.view]`.
    // `setTenant` pula memerlukan pengguna berautentikasi (TenantSet::$user bukan-null).
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mam, isQuiet: true);
    $this->actingAs($this->kerani);
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});

/** Halaman Peti Masuk hidup dalam panel app + tenant MAM. */
function inboxPage(): Testable
{
    return Livewire::test(ListInbox::class, ['tenant' => test()->mam]);
}

it('§6.5 sasaran tour muat naik didaftarkan pada aksi sebenar, bukan hanya dalam sumber', function () {
    $page = inboxPage();
    $action = $page->instance()->getAction('muatNaik');

    expect($action)->toBeInstanceOf(Action::class)
        // Pencetus butang "+ Muat Naik Dokumen".
        ->and($action->getExtraAttributes())->toMatchArray(['data-help-target' => 'inbox-upload'])
        // Butang Hantar: `modalSubmitAction()` membungkus aksi INDUK. Menggantinya (memanggil
        // `->action()`/`->submit()` semula) akan memusnahkan penghantaran borang — pelajaran F4.
        ->and($action->getModalSubmitAction()->getExtraAttributes())
        ->toMatchArray(['data-help-target' => 'inbox-upload-submit']);

    // Dropzone: FileUpload dalam skema aksi. `getSchema()` Filament 4 memerlukan bekas
    // Schema yang terikat kepada komponen Livewire — jadi bekas dibina daripada halaman itu.
    $files = collect($action->getSchema(Schema::make($page->instance()))->getComponents())
        ->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'files');

    expect($files)->not->toBeNull('skema aksi muat naik tiada medan `files`')
        ->and($files->getExtraAttributes())->toMatchArray(['data-help-target' => 'inbox-upload-dropzone']);
});

it('§6.5 matriks: kuota penuh → notifikasi merah, 0 rekod dicipta', function () {
    // Pintu 1 §5.14: muat naik disekat apabila kuota penuh; bacaan kekal OK.
    $quota = Mockery::mock(QuotaService::class);
    $quota->shouldReceive('isFull')->andReturnTrue();
    $quota->shouldReceive('usedBytes')->andReturn(0);
    $quota->shouldReceive('effectiveQuotaBytes')->andReturn(0);
    $quota->shouldReceive('usagePercent')->andReturn(100.0);
    app()->instance(QuotaService::class, $quota);

    $sebelum = Record::query()->withoutGlobalScope('mosque')->count();

    inboxPage()
        ->callAction('muatNaik', ['files' => ['inbox-tmp/apa-apa.pdf'], 'file_names' => []])
        ->assertNotified('Kuota storan penuh');

    expect(Record::query()->withoutGlobalScope('mosque')->count())->toBe($sebelum,
        'kuota penuh tetapi rekod tetap dicipta — pintu 1 §5.14 tidak berkuat kuasa');
});

it('§6.5 matriks: status antivirus dipaparkan pada baris Peti Masuk', function () {
    // Langkah 4 tour menyuruh pengguna "Semak Antivirus, OCR dan Sumber" — jadi lajur itu
    // mesti benar-benar dipaparkan, bukan sekadar disimpan dalam DB.
    $svc = app(InboxIngestService::class);

    $bersih = $svc->ingest($this->mam, 'a', 'bersih.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);
    $jangkit = $svc->ingest($this->mam, 'b', 'jangkit.pdf', 'application/pdf', $this->kerani, SourceChannel::MuatNaik);

    $bersih->forceFill(['virus_scan_status' => 'clean'])->save();
    $jangkit->forceFill(['virus_scan_status' => 'infected'])->save();

    inboxPage()
        ->assertCanSeeTableRecords([$bersih, $jangkit])
        ->assertTableColumnExists('virus_scan_status')
        ->assertTableColumnStateSet('virus_scan_status', 'clean', $bersih)
        ->assertTableColumnStateSet('virus_scan_status', 'infected', $jangkit);
});
