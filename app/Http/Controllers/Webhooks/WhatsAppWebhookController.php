<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppInboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// §11.1 — Webhook masuk (gateway → Diwan). Hanya dokumen layak (kata kunci di sisi gateway).
class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request, WhatsAppInboundService $inbound): JsonResponse
    {
        // (1) HMAC-SHA256 wajib.
        $raw = $request->getContent();
        $signature = $request->header('X-Signature') ?: $request->header('X-Diwan-Signature');
        $secret = (string) config('diwan.whatsapp.webhook_secret');

        if ($secret === '') {
            return response()->json([], 401);
        }

        $expected = hash_hmac('sha256', $raw, $secret);
        $provided = str_starts_with((string) $signature, 'sha256=')
            ? substr((string) $signature, 7)
            : (string) $signature;

        if (! $signature || ! hash_equals($expected, $provided)) {
            return response()->json([], 401); // tanpa maklumat
        }

        $body = $request->json()->all();
        if (isset($body['event']) && $body['event'] !== 'message.received') {
            return response()->json([]);
        }
        $data = isset($body['data']) && is_array($body['data']) ? $body['data'] : $body;
        $inbound->handle($data);

        return response()->json([]);
    }
}
