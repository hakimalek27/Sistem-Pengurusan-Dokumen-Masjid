<?php

use App\Enums\MinitPriority;
use App\Enums\MinitStatus;
use App\Models\LoginToken;
use App\Models\Minit;
use App\Notifications\InboxNewItemNotification;
use App\Notifications\MinitRoutedNotification;
use Illuminate\Notifications\AnonymousNotifiable;

/*
 * §14 / §15.1 — Notifikasi minit/kelulusan/peti masuk membawa pautan magic
 * deep-link PER PENERIMA (auto-login → terus ke sasaran).
 */

beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->record = makeRecord($this->mam, makeFile($this->mam, makeNode($this->mam, '100-1')));
    $this->from = makeMember($this->mam, 'admin_masjid', 'from@mam.test');
});

function makeThreadMinit($mam, $record, $from): Minit
{
    return Minit::query()->create([
        'mosque_id' => $mam->id,
        'record_id' => $record->id,
        'from_user_id' => $from->id,
        'body' => 'Sila ambil tindakan segera.',
        'priority' => MinitPriority::Biasa,
        'due_at' => now()->addDays(7)->toDateString(),
        'status' => MinitStatus::Terbuka,
    ]);
}

it('setiap penerima dapat pautan magic /masuk/ berbeza (deep-link ke rekod)', function () {
    $a = makeMember($this->mam, 'kerani', 'a@mam.test');
    $b = makeMember($this->mam, 'pengerusi', 'b@mam.test');

    $notif = new MinitRoutedNotification(makeThreadMinit($this->mam, $this->record, $this->from), 'tindakan');

    $msgA = $notif->waMessage($a);
    $msgB = $notif->waMessage($b);

    expect($msgA)->toContain(url('/masuk/'))
        ->and($msgB)->toContain(url('/masuk/'))
        ->and($msgA)->not->toBe($msgB); // token berbeza per penerima

    $tokens = LoginToken::query()->get();
    expect($tokens)->toHaveCount(2);
    $tokens->each(function ($t) {
        expect($t->purpose)->toBe('notification')
            ->and($t->intended_url)->toBe('/r/'.$this->record->ulid);
    });
    expect($tokens->pluck('user_id')->sort()->values()->all())->toBe(collect([$a->id, $b->id])->sort()->values()->all());
});

it('memo: satu penerima → satu token walau merentas saluran (waMessage+toMail+toWhatsApp+toTelegram)', function () {
    $a = makeMember($this->mam, 'kerani', 'a@mam.test', ['telegram_chat_id' => '999']);

    $notif = new MinitRoutedNotification(makeThreadMinit($this->mam, $this->record, $this->from), 'tindakan');
    $notif->waMessage($a);
    $notif->toMail($a);
    $notif->toWhatsApp($a);
    $notif->toTelegram($a);

    expect(LoginToken::query()->count())->toBe(1);
});

it('penerima bukan User (AnonymousNotifiable) → pautan biasa tanpa token', function () {
    $notif = new InboxNewItemNotification($this->mam, 3, 'muat naik');

    $msg = $notif->waMessage(new AnonymousNotifiable);

    expect($msg)->toContain('/app/mam/peti-masuk')
        ->and($msg)->not->toContain('/masuk/')
        ->and(LoginToken::query()->count())->toBe(0);
});

it('InboxNewItem deep-link menyasarkan Peti Masuk (/app/{slug}/peti-masuk)', function () {
    $admin = makeMember($this->mam, 'admin_masjid', 'admin2@mam.test');
    $notif = new InboxNewItemNotification($this->mam, 2, 'e-mel');

    $notif->waMessage($admin);

    $token = LoginToken::query()->first();
    expect($token->intended_url)->toBe('/app/mam/peti-masuk')
        ->and($token->purpose)->toBe('notification');
});
