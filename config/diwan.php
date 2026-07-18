<?php

return [

    // Disk storan blob (§17): 'cos' produksi, 'local' dev, Storage::fake dalam ujian.
    'storage_disk' => env('DIWAN_STORAGE_DISK', 'cos'),

    // Bahasa OCR tesseract (§12).
    'ocr_langs' => env('OCR_LANGS', 'msa+eng'),

    // Lokasi model bahasa Tesseract (Scoop Windows boleh memasangnya berasingan).
    'tessdata_prefix' => env('TESSDATA_PREFIX'),

    // Had teks OCR disimpan (§5.7).
    'ocr_text_limit' => 1_000_000,

    // Had saiz muat naik (MB) — selaras php.ini (§15.7).
    'max_upload_mb' => 25,

    // Had kadar cubaan log masuk seminit (§15.1). Produksi kekal 5;
    // naikkan HANYA dalam persekitaran e2e (banyak peranan log masuk berturut).
    'login_rate_limit' => (int) env('DIWAN_LOGIN_RATE_LIMIT', 5),

    // MIME dibenarkan (§15.7).
    'allowed_mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'docx', 'xlsx', 'pptx'],

    // Bilangan kandungan sebelum cadang tutup jilid (Aliran F §10).
    'enclosure_volume_limit' => 100,

    // Gateway WhatsApp (§11.1).
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'gateway'),   // gateway | log
        'gateway_url' => env('WHATSAPP_GATEWAY_URL'),
        'gateway_token' => env('WHATSAPP_GATEWAY_TOKEN'),
        'provisioning_secret' => env('WHATSAPP_PROVISIONING_SECRET'),
        'instance_id' => env('DIWAN_INSTANCE_ID', 'spdm-local'),
        'webhook_secret' => env('WHATSAPP_WEBHOOK_SECRET'),
        'webhook_url' => env('WHATSAPP_WEBHOOK_URL', rtrim((string) env('APP_URL'), '/').'/api/webhooks/whatsapp'),
        'default_keyword' => 'spdm',                      // lalai kata kunci intake
        'timeout' => 8,                                   // saat (§11.1)
    ],

    // Ingest e-mel pengimbas (§11.3).
    'imap_enabled' => filter_var(env('IMAP_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'imap' => [
        'host' => env('IMAP_HOST'),
        'port' => (int) env('IMAP_PORT', 993),
    ],
    'mail_intake' => [
        'default_keyword' => env('MAIL_INTAKE_KEYWORD', 'spdm'),
        // Alamat intake rasmi (cth scan@bakwim.my) untuk plus-addressing per masjid.
        // Jika kosong, jatuh balik ke IMAP username (config imap.accounts.default.username).
        'address' => env('MAIL_INTAKE_ADDRESS'),
    ],

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST'),
        'key' => env('MEILISEARCH_KEY'),
    ],

    // Telegram (§11.2).
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    // SLA minit — hari lalai ikut keutamaan (§9.C.5, Aliran E).
    'sla' => [
        'biasa' => 7,
        'segera' => 3,
        'kritikal' => 1,
    ],

    // Kuota & retensi lalai.
    'default_quota_gb' => (int) env('DIWAN_DEFAULT_QUOTA_GB', 20),
    'default_retention_years' => (int) env('DIWAN_DEFAULT_RETENTION_YEARS', 7),

    // Pendaftaran masjid awam dibuka?
    'registration_open' => filter_var(env('DIWAN_REGISTRATION_OPEN', true), FILTER_VALIDATE_BOOLEAN),

    // Blok saiz add-on storan lalai (GB) — superadmin boleh ubah di platform_settings.
    'storage_block_gb' => 10,

    // 16 negeri Malaysia (dropdown pendaftaran §9.A).
    'states' => [
        'Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang',
        'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor',
        'Terengganu', 'W.P. Kuala Lumpur', 'W.P. Labuan', 'W.P. Putrajaya',
    ],

];
