<?php

use App\Http\Controllers\MagicLoginController;
use App\Http\Controllers\RecordDeepLinkController;
use App\Livewire\RegisterMosque;
use App\Livewire\RequestMagicLink;
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

// §9.A — Deep-link rekod (auth; pengesahan keahlian tenant / 404)
Route::get('/r/{ulid}', RecordDeepLinkController::class)
    ->middleware('auth')
    ->name('record.deeplink');
