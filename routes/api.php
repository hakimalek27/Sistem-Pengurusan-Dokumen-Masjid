<?php

use App\Http\Controllers\Webhooks\TelegramWebhookController;
use App\Http\Controllers\Webhooks\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// §11.1 — Webhook WhatsApp (HMAC dalam controller). Throttle 60/min (§15.1).
Route::post('/webhooks/whatsapp', WhatsAppWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.whatsapp');

// §11.2 — Webhook Telegram (rahsia dalam path).
Route::post('/webhooks/telegram/{secret}', TelegramWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.telegram');
