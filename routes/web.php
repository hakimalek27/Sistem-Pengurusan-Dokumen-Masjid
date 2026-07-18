<?php

use App\Http\Controllers\MagicLoginController;
use App\Http\Controllers\RecordDeepLinkController;
use App\Http\Controllers\SecureArtifactController;
use App\Http\Controllers\SecureFileController;
use App\Livewire\RegisterMosque;
use App\Livewire\RequestMagicLink;
use App\Livewire\SetFirstPassword;
use Illuminate\Support\Facades\Route;

// §9.A — Halaman awam
Route::get('/', fn () => view('welcome'))->name('home');

Route::get('/daftar', RegisterMosque::class)
    ->middleware('throttle:20,60')
    ->name('daftar');

Route::get('/log-masuk', RequestMagicLink::class)
    ->middleware('throttle:60,1')
    ->name('log-masuk');

// §15.1 — Magic link (sekali guna; sahihan dalam controller)
Route::get('/masuk/{token}', MagicLoginController::class)
    ->middleware('throttle:10,1')
    ->name('magic-login');

// Fasa B — tetapkan kata laluan kali pertama (akaun magic-link tanpa kata laluan).
// Route di luar panel: tiada middleware EnsurePasswordIsSet → tiada gelung.
Route::get('/tetapkan-kata-laluan', SetFirstPassword::class)
    ->middleware('auth')
    ->name('password.first');

// §9.A — Deep-link rekod (auth; pengesahan keahlian tenant / 404)
Route::get('/r/{ulid}', RecordDeepLinkController::class)
    ->middleware('auth')
    ->name('record.deeplink');

Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('/secure-file/{media}', SecureFileController::class)->name('secure-file.show');
    Route::get('/secure-artifact/invoice/{order}', [SecureArtifactController::class, 'invoice'])->name('secure-artifact.invoice');
    Route::get('/secure-artifact/certificate/{batch}', [SecureArtifactController::class, 'certificate'])->name('secure-artifact.certificate');
    Route::get('/secure-artifact/export/{export}', [SecureArtifactController::class, 'export'])->name('secure-artifact.export');
});
