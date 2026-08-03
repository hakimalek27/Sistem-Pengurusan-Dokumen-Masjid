<?php

use App\Enums\ApprovalStatus;
use App\Enums\MinitPriority;
use App\Enums\MinitStatus;
use App\Enums\OrderStatus;
use App\Filament\App\Pages\OnboardingWizard;
use App\Livewire\RegisterMosque;
use App\Models\Approval;
use App\Models\Minit;
use App\Models\StorageOrder;
use App\Models\StoredExport;
use App\Models\User;
use App\Notifications\AddonExpiringNotification;
use App\Notifications\ApprovalDecidedNotification;
use App\Notifications\ApprovalRequestedNotification;
use App\Notifications\AutoDisposalDoneNotification;
use App\Notifications\ConnectionAlertNotification;
use App\Notifications\DriveBackupAlertNotification;
use App\Notifications\ExportReadyNotification;
use App\Notifications\GatewayDownNotification;
use App\Notifications\GuidanceDigestNotification;
use App\Notifications\InboxNewItemNotification;
use App\Notifications\MailIntakeRejectedNotification;
use App\Notifications\MinitCompletedNotification;
use App\Notifications\MinitReminderNotification;
use App\Notifications\MinitRoutedNotification;
use App\Notifications\NewStorageOrderNotification;
use App\Notifications\QuotaThresholdNotification;
use App\Notifications\RetentionNoticeNotification;
use App\Notifications\TestNotification;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Wizard;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

/**
 * F3 §4.7 — penjaga bahasa. Menutup RR-01-03 / RR-03-01 / RR-02-05 / RR-05-02 /
 * RR-05-01 / RR-08-04 / RR-01-05.
 *
 * Setiap ujian menetapkan locale `ms` SECARA EKSPLISIT dan memulihkannya selepas
 * (§4.7 P2) — suite tidak boleh bergantung pada susunan ujian mahupun pada
 * `APP_LOCALE` persekitaran, kerana phpunit.xml tidak menetapkannya.
 */

/** Rentetan kerangka vendor yang membuktikan terjemahan TIDAK diambil. */
const EN_FRAMEWORK_LEAK = [
    'Hello!',
    'Whoops!',
    'Regards,',
    'All rights reserved.',
    "If you're having trouble",
];

/** Perkataan fungsi Inggeris — kehadirannya dalam subjek/label = teks belum diterjemah. */
const EN_FUNCTION_WORDS = '/\b(?:the|and|your|please|is required|has been|will be|must be)\b/i';

/**
 * Data-provider EKSPLISIT (§4.3) — satu entri per kelas notifikasi ber-`toMail()`.
 * Senarai ini dibandingkan dengan imbasan fail sebenar oleh penjaga kesempurnaan
 * di bawah, jadi kelas baharu tanpa fixture akan memerahkan suite.
 */
const NOTIFICATION_MAIL_CLASSES = [
    AddonExpiringNotification::class,
    ApprovalDecidedNotification::class,
    ApprovalRequestedNotification::class,
    AutoDisposalDoneNotification::class,
    ConnectionAlertNotification::class,
    DriveBackupAlertNotification::class,
    ExportReadyNotification::class,
    GatewayDownNotification::class,
    GuidanceDigestNotification::class,
    InboxNewItemNotification::class,
    MailIntakeRejectedNotification::class,
    MinitCompletedNotification::class,
    MinitReminderNotification::class,
    MinitRoutedNotification::class,
    NewStorageOrderNotification::class,
    QuotaThresholdNotification::class,
    RetentionNoticeNotification::class,
    TestNotification::class,
];

function withLocale(string $locale, Closure $fn): mixed
{
    $asal = app()->getLocale();
    app()->setLocale($locale);

    try {
        return $fn();
    } finally {
        app()->setLocale($asal);
    }
}

/** Semua kelas `app/Notifications/*.php` yang benar-benar ber-`toMail()`. */
function notificationClassesWithToMail(): array
{
    return collect(glob(app_path('Notifications/*.php')))
        ->filter(fn (string $p) => str_contains((string) file_get_contents($p), 'public function toMail'))
        ->map(fn (string $p) => 'App\\Notifications\\'.basename($p, '.php'))
        ->sort()->values()->all();
}

/**
 * Fixture eksplisit per kelas (§4.3): refleksi automatik ke atas constructor
 * pelbagai bentuk adalah rapuh — satu entri bertulis per kelas jauh lebih stabil.
 *
 * @return array{0: object, 1: User}
 */
function notificationFixture(string $class): array
{
    $mosque = makeMosque('MAM', 'mam');
    $penerima = makeMember($mosque, 'admin_masjid', 'penerima@ujian.test');
    $pemohon = makeMember($mosque, 'kerani', 'pemohon@ujian.test');
    $node = makeNode($mosque, '100');
    $file = makeFile($mosque, $node);
    $record = makeRecord($mosque, $file);

    $minit = fn () => Minit::query()->create([
        'mosque_id' => $mosque->id,
        'record_id' => $record->id,
        'from_user_id' => $pemohon->id,
        'body' => 'Sila ambil tindakan segera.',
        'priority' => MinitPriority::Segera,
        'due_at' => now()->addDays(3)->toDateString(),
        'status' => MinitStatus::Terbuka,
    ]);

    $approval = fn (ApprovalStatus $status) => Approval::query()->create([
        'mosque_id' => $mosque->id,
        'record_id' => $record->id,
        'requested_by' => $pemohon->id,
        'approver_id' => $penerima->id,
        'status' => $status,
        'request_note' => 'Mohon semakan.',
    ]);

    $notification = match ($class) {
        AddonExpiringNotification::class => new $class($mosque, 20, 7, now()->addDays(7)->toDateString()),
        ApprovalDecidedNotification::class => new $class($approval(ApprovalStatus::Lulus)),
        ApprovalRequestedNotification::class => new $class($approval(ApprovalStatus::Menunggu)),
        AutoDisposalDoneNotification::class => new $class($mosque, 3, 7),
        ConnectionAlertNotification::class => new $class('Sesi WhatsApp terputus', 'Sesi masjid tidak lagi bersambung.'),
        DriveBackupAlertNotification::class => new $class('Token Google Drive tidak lagi sah.'),
        ExportReadyNotification::class => new $class($mosque, StoredExport::query()->create([
            'mosque_id' => $mosque->id,
            'requested_by' => $pemohon->id,
            'label' => 'ujian',
            'path' => 'exports/ujian.zip',
            'expires_at' => now()->addDay(),
        ])),
        GatewayDownNotification::class => new $class(now()->subHour()->toDateTimeString()),
        GuidanceDigestNotification::class => new $class($mosque, ['3 minit menunggu tindakan.'], ['mail']),
        InboxNewItemNotification::class => new $class($mosque, 2, 'emel'),
        MailIntakeRejectedNotification::class => new $class($mosque, 'kata kunci tiada', 'orang@luar.test', 'Dokumen'),
        MinitCompletedNotification::class => new $class($minit()),
        MinitReminderNotification::class => new $class($minit(), true, 2),
        MinitRoutedNotification::class => new $class($minit()),
        NewStorageOrderNotification::class => new $class(StorageOrder::query()->create([
            'mosque_id' => $mosque->id,
            'ordered_by' => $pemohon->id,
            'gb' => 10,
            'unit_price_cents' => 1000,
            'amount_cents' => 10000,
            'period_months' => 12,
            'status' => OrderStatus::MenungguBayaran,
            'invoice_no' => 'INV-UJIAN-1',
            'idempotency_key' => 'ujian-'.uniqid(),
        ])),
        QuotaThresholdNotification::class => new $class($mosque, 80, 16.0, 20.0),
        RetentionNoticeNotification::class => new $class($mosque, 5, 30, 7),
        TestNotification::class => new $class,
        default => throw new RuntimeException("Daftar fixture untuk kelas {$class} dalam notificationFixture() — §4.3 menuntut liputan 18/18."),
    };

    return [$notification, $penerima];
}

/*
|--------------------------------------------------------------------------
| 1. Kesempurnaan kunci — hanya 4 fail yang KITA terbitkan (§4.7 #1)
|--------------------------------------------------------------------------
| Skop sengaja terhad: membandingkan keseluruhan `lang/en` vendor akan
| memerahkan CI pada setiap naik taraf framework tanpa sebarang faedah.
*/

test('setiap kunci lang/en wujud dalam lang/ms (4 fail diterbitkan)', function (string $fail) {
    $en = require lang_path("en/{$fail}.php");
    $ms = require lang_path("ms/{$fail}.php");

    $ratakan = function (array $arr, string $awalan = '') use (&$ratakan): array {
        $keluar = [];
        foreach ($arr as $k => $v) {
            $kunci = $awalan === '' ? (string) $k : "{$awalan}.{$k}";
            if (is_array($v)) {
                $keluar = array_merge($keluar, $ratakan($v, $kunci));
            } else {
                $keluar[] = $kunci;
            }
        }

        return $keluar;
    };

    $hilang = array_values(array_diff($ratakan($en), $ratakan($ms)));

    expect($hilang)->toBe([], "lang/ms/{$fail}.php kehilangan kunci: ".implode(', ', $hilang));
})->with(['validation', 'auth', 'passwords', 'pagination']);

/*
|--------------------------------------------------------------------------
| 2. E-mel BM penuh — 18/18 (§4.7 #2)
|--------------------------------------------------------------------------
*/

test('e-mel notifikasi BM penuh dari salam hingga footer', function (string $class) {
    [$notification, $penerima] = notificationFixture($class);

    $mail = withLocale('ms', fn () => $notification->toMail($penerima));
    $html = withLocale('ms', fn () => (string) $mail->render());

    // (a) subjek BM
    expect($mail->subject)->not->toBeEmpty("{$class}: subjek kosong")
        ->and($mail->subject)->not->toMatch(EN_FUNCTION_WORDS);

    // (b) salam BM (atau salam tersuai yang sudah BM)
    expect($html)->toContain($mail->greeting ?: 'Salam sejahtera,');

    // (c) teks butang aksi BM
    if (filled($mail->actionText)) {
        expect($mail->actionText)->not->toMatch(EN_FUNCTION_WORDS, "{$class}: teks butang bukan BM");
    }

    // (d) footer + subcopy BM
    expect($html)->toContain('Hak cipta terpelihara.')
        ->and($html)->toContain('Sekian,');
    if (filled($mail->actionText)) {
        expect($html)->toContain('Jika anda menghadapi masalah menekan butang');
    }

    // (e) TIADA kerangka Inggeris yang tertinggal
    foreach (EN_FRAMEWORK_LEAK as $bocor) {
        expect(str_contains($html, $bocor))
            ->toBeFalse("{$class}: kerangka Inggeris '{$bocor}' masih dipaparkan");
    }
})->with(NOTIFICATION_MAIL_CLASSES);

test('data-provider melitupi TEPAT kelas ber-toMail dalam app/Notifications (18/18)', function () {
    $daripadaFail = notificationClassesWithToMail();
    $daripadaProvider = collect(NOTIFICATION_MAIL_CLASSES)->sort()->values()->all();

    expect($daripadaFail)->toHaveCount(18,
        'baseline §4.3 = 18 kelas; kemas kini denominator + fixture jika berubah');

    $tiadaFixture = array_values(array_diff($daripadaFail, $daripadaProvider));
    expect($tiadaFixture)->toBe([],
        'daftar fixture untuk kelas: '.implode(', ', $tiadaFixture));

    $lapuk = array_values(array_diff($daripadaProvider, $daripadaFail));
    expect($lapuk)->toBe([],
        'entri provider merujuk kelas yang tiada toMail(): '.implode(', ', $lapuk));
});

test('render notifikasi dalam ms tidak mencemarkan locale permintaan lain', function () {
    app()->setLocale('en');
    [$notification, $penerima] = notificationFixture(TestNotification::class);

    withLocale('ms', fn () => (string) $notification->toMail($penerima)->render());

    expect(app()->getLocale())->toBe('en');
});

test('kunci JSON yang tiada jatuh ke teks Inggeris yang boleh dibaca, bukan kunci mentah', function () {
    // Locale tanpa fail terjemahan langsung — meniru kunci yang tercicir.
    $mesej = withLocale('zz', fn () => __('Hello!'));

    expect($mesej)->toBe('Hello!')
        ->and($mesej)->not->toStartWith('messages.')
        ->and($mesej)->not->toContain('::');
});

/*
|--------------------------------------------------------------------------
| 3. Lookup terus kunci JSON (§4.7 #3)
|--------------------------------------------------------------------------
*/

test('5 kunci JSON kerangka e-mel dipetakan verbatim', function () {
    withLocale('ms', function () {
        expect(__('Hello!'))->toBe('Salam sejahtera,')
            ->and(__('Whoops!'))->toBe('Harap maaf!')
            ->and(__('Regards,'))->toBe('Sekian,')
            ->and(__('All rights reserved.'))->toBe('Hak cipta terpelihara.');

        // Kunci subcopy mengandungi baris baharu SEBENAR — satu aksara tersasar
        // dan terjemahan gagal secara senyap.
        $subcopy = "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:";
        expect(__($subcopy, ['actionText' => 'Log masuk']))
            ->toBe('Jika anda menghadapi masalah menekan butang "Log masuk", salin dan tampal URL di bawah ke pelayar web anda:');
    });
});

/*
|--------------------------------------------------------------------------
| 4. Validasi BM (§4.7 #4)
|--------------------------------------------------------------------------
*/

test('borang pendaftaran kosong memberi mesej BM, bukan "The … field is required"', function () {
    cache()->flush(); // had kadar /daftar 3/jam dikongsi antara ujian

    withLocale('ms', function () {
        $ralat = Livewire::test(RegisterMosque::class)
            ->call('nextStep')
            ->errors()
            ->all();

        expect($ralat)->not->toBeEmpty();

        foreach ($ralat as $mesej) {
            expect($mesej)->toContain('wajib diisi')
                ->and($mesej)->not->toContain('field is required')
                ->and($mesej)->not->toStartWith('The ');
        }

        // Nama medan mengikut label UI, bukan nama lajur teknikal.
        expect(implode(' ', $ralat))->toContain('Nama');
    });
});

test('placeholder :min/:max dan trans_choice berfungsi dalam BM', function () {
    withLocale('ms', function () {
        $v = Validator::make(['code' => 'ab'], ['code' => 'min:3']);
        expect($v->errors()->first('code'))
            ->toContain('sekurang-kurangnya 3 aksara')
            ->and($v->errors()->first('code'))->toContain('Kod Akronim');

        $v2 = Validator::make(['title' => str_repeat('a', 300)], ['title' => 'max:255']);
        expect($v2->errors()->first('title'))->toContain('255 aksara');

        // pagination melalui trans() — bukti parameter/format terjemahan dimuat
        expect(trans('pagination.next'))->toBe('Seterusnya &raquo;')
            ->and(trans('pagination.previous'))->toBe('&laquo; Sebelumnya');
    });
});

/*
|--------------------------------------------------------------------------
| 5. Label wizard vendor (§4.7 #5)
|--------------------------------------------------------------------------
*/

test('override vendor wizard memberi Seterusnya/Sebelumnya', function () {
    withLocale('ms', function () {
        expect(__('filament-schemas::components.wizard.actions.next_step.label'))->toBe('Seterusnya')
            ->and(__('filament-schemas::components.wizard.actions.previous_step.label'))->toBe('Sebelumnya');
    });
});

test('butang wizard yang dibina Filament berbunyi Seterusnya/Sebelumnya', function () {
    withLocale('ms', function () {
        // Label butang dijana oleh Wizard::getNextAction()/getPreviousAction()
        // (vendor Wizard.php:164,194) — mengassert di sini menguji objek yang
        // BENAR-BENAR dirender, bukan sekadar kewujudan kunci dalam fail lang.
        $wizard = Wizard::make([]);

        expect($wizard->getNextAction()->getLabel())->toBe('Seterusnya')
            ->and($wizard->getPreviousAction()->getLabel())->toBe('Sebelumnya');
    });
});

test('ketiga-tiga wizard projek menggunakan komponen yang override ini terpakai', function () {
    // Jika projek berpindah ke komponen langkah lain, override di atas menjadi
    // tidak relevan secara senyap — ujian ini menangkapnya.
    $penggunaWizard = [
        'app/Filament/App/Pages/OnboardingWizard.php',
        'app/Filament/App/Resources/Inbox/Tables/InboxTable.php',
        'app/Filament/App/Resources/Records/Pages/ListRecords.php',
    ];

    foreach ($penggunaWizard as $fail) {
        // Nota: `toContain()` Pest menerima needle VARIADIK — mesej tidak boleh
        // diletak sebagai argumen kedua (ia akan jadi needle kedua).
        expect(str_contains((string) file_get_contents(base_path($fail)), 'Filament\Schemas\Components\Wizard\Step'))
            ->toBeTrue("{$fail} tidak lagi guna Wizard\\Step — override label wizard mungkin tidak lagi relevan");
    }
});

test('halaman berwizard tidak membocorkan ejaan pendek "Seterus"/"Sebelum"', function () {
    $mosque = makeMosque('MAM', 'mam');
    $admin = makeMember($mosque, 'admin_masjid', 'admin@mam.test');

    Filament::setTenant($mosque, isQuiet: true);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    $this->actingAs($admin);

    withLocale('ms', function () {
        // Nota: kandungan modal Filament 4 dirender pelanggan-sisi, jadi butang
        // wizard TIDAK hadir dalam HTML pelayan — label itu diuji di atas melalui
        // objek Action, dan hujung-ke-hujung dalam e2e. Di sini kita menjaga
        // badan halaman daripada ejaan pendek.
        $html = Livewire::test(OnboardingWizard::class)->html();

        expect($html)->not->toContain('Seterus<')
            ->and($html)->not->toContain('>Seterus ')
            ->and($html)->not->toContain('Sebelum<');
    });

    Filament::setTenant(null, isQuiet: true);
});

/*
|--------------------------------------------------------------------------
| 7. Lima label Edit → Sunting (§4.7 #7, C10)
|--------------------------------------------------------------------------
*/

test('tiada label Edit Inggeris tertinggal dalam kod ATAU dalam teks arahan', function () {
    // Penjaga yang lebih luas daripada inventori §4.6 (5 label `->label()`).
    // Ia dicipta kerana kejadian KEENAM ditemui semasa pelaksanaan: teks arahan
    // dalam blade yang MENYEBUT butang itu (“Edit Tetapan”). Membiarkannya =
    // arahan merujuk butang yang tidak wujud — kesilapan yang sama seperti
    // “Seterus” dalam katalog (§4.5).
    $corak = ["label('Edit", 'label("Edit', '>Edit<', 'Edit Tetapan', 'Edit Tenant'];
    $jumpa = [];

    foreach ([app_path(), resource_path('views'), resource_path('help')] as $akar) {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar));
        foreach ($rii as $fail) {
            if (! $fail->isFile() || ! in_array($fail->getExtension(), ['php', 'json'], true)) {
                continue;
            }
            $isi = (string) file_get_contents($fail->getPathname());
            foreach ($corak as $c) {
                if (str_contains($isi, $c)) {
                    $jumpa[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $fail->getPathname())." → {$c}";
                }
            }
        }
    }

    expect($jumpa)->toBe([], "Label/rujukan 'Edit' Inggeris masih ada:\n".implode("\n", $jumpa));
});

test('tiada label Edit Inggeris pada lima halaman §4.6', function () {
    $mosque = makeMosque('MAM', 'mam');
    $admin = makeMember($mosque, 'admin_masjid', 'admin@mam.test');
    $superadmin = makeMember($mosque, 'admin_masjid', 'super@ujian.test', ['is_superadmin' => true]);

    $semak = function (string $url, $user) {
        $html = $this->actingAs($user)->get($url)->assertOk()->getContent();

        foreach (['>Edit<', 'Edit Tetapan', 'Edit Tenant'] as $label) {
            expect(str_contains($html, $label))->toBeFalse("{$url}: label '{$label}' masih ada");
        }

        return $html;
    };

    $semak('/admin/users', $superadmin);
    $semak('/admin/mosques', $superadmin);
    $semak('/admin/mosques/'.$mosque->id, $superadmin);
    expect($semak('/admin/tetapan-platform', $superadmin))->toContain('Sunting Tetapan');

    Filament::setTenant($mosque, isQuiet: true);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    expect($semak('/app/mam/tetapan-masjid', $admin))->toContain('Sunting Tetapan');
    Filament::setTenant(null, isQuiet: true);
});
