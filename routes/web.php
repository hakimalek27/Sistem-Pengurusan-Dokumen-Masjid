<?php

use App\Http\Controllers\DocumentViewerController;
use App\Http\Controllers\GoogleDriveCallbackController;
use App\Http\Controllers\HelpImageController;
use App\Http\Controllers\MagicLoginController;
use App\Http\Controllers\RecordDeepLinkController;
use App\Http\Controllers\SecureArtifactController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\SupportAttachmentController;
use App\Livewire\PublicHelpCenter;
use App\Livewire\RegisterMosque;
use App\Livewire\RequestMagicLink;
use App\Livewire\SetFirstPassword;
use Illuminate\Support\Facades\Route;

// §9.A — Halaman awam
Route::get('/', fn () => view('welcome'))->name('home');

Route::get('/daftar', RegisterMosque::class)
    ->middleware('throttle:public-registration')
    ->name('daftar');

Route::get('/log-masuk', RequestMagicLink::class)
    ->middleware('throttle:public-login-page')
    ->name('log-masuk');

Route::get('/bantuan', PublicHelpCenter::class)
    ->middleware('throttle:public-help')
    ->name('bantuan');

Route::get('/bantuan/imej/{guideId}', HelpImageController::class)
    ->middleware('throttle:public-help')
    ->name('help-image.show');

// §15.1 — Magic link auto-login. GET = interstisial (elak bot pratonton
// membakar token sekali-guna); POST = guna token + log masuk + deep-link.
Route::get('/masuk/{token}', [MagicLoginController::class, 'show'])
    ->middleware('throttle:magic-login')
    ->name('magic-login');
Route::post('/masuk/{token}', [MagicLoginController::class, 'consume'])
    ->middleware('throttle:magic-login')
    ->name('magic-login.consume');

// Fasa B — tetapkan kata laluan kali pertama (akaun magic-link tanpa kata laluan).
// Route di luar panel: tiada middleware EnsurePasswordIsSet → tiada gelung.
Route::get('/tetapkan-kata-laluan', SetFirstPassword::class)
    ->middleware('auth')
    ->name('password.first');

// §9.A — Deep-link rekod (auth; pengesahan keahlian tenant / 404)
Route::get('/r/{ulid}', RecordDeepLinkController::class)
    ->middleware('auth')
    ->name('record.deeplink');

// §4.6′ — Callback OAuth Google Drive (superadmin sahaja; sahihan dalam controller)
Route::get('/gdrive/callback', GoogleDriveCallbackController::class)
    ->middleware('auth')
    ->name('gdrive.callback');

Route::middleware(['auth', 'signed'])->group(function () {
    Route::get('/viewer/{media}', DocumentViewerController::class)->name('document-viewer.show');
    Route::get('/secure-file/{media}', SecureFileController::class)->name('secure-file.show');
    Route::get('/secure-artifact/invoice/{order}', [SecureArtifactController::class, 'invoice'])->name('secure-artifact.invoice');
    Route::get('/secure-artifact/certificate/{batch}', [SecureArtifactController::class, 'certificate'])->name('secure-artifact.certificate');
    Route::get('/secure-artifact/export/{export}', [SecureArtifactController::class, 'export'])->name('secure-artifact.export');
});

Route::get('/support-attachment/{attachment}', SupportAttachmentController::class)
    ->middleware(['auth', 'throttle:30,1'])
    ->name('support-attachment.show');
