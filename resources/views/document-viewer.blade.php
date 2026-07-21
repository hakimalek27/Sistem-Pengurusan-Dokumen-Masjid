<!doctype html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $record->title ?: 'Viewer Dokumen' }}</title>
    @vite(['resources/css/app.css', 'resources/js/document-viewer.js'])
    <style>
        body { margin: 0; background: #e5e7eb; color: #111827; font-family: ui-sans-serif, system-ui, sans-serif; }
        .viewer-toolbar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; min-height: 3.5rem; padding: .65rem 1rem; background: #fff; border-bottom: 1px solid #d1d5db; }
        .viewer-toolbar button, .viewer-toolbar a, .viewer-toolbar input { min-height: 2.25rem; border: 1px solid #9ca3af; border-radius: 6px; background: #fff; padding: .35rem .65rem; color: #111827; }
        .viewer-toolbar button:hover, .viewer-toolbar a:hover { background: #f3f4f6; }
        .viewer-toolbar input[type=number] { width: 4.25rem; }
        .viewer-toolbar input[type=search] { width: min(18rem, 45vw); }
        .viewer-title { min-width: 12rem; flex: 1; font-weight: 600; overflow-wrap: anywhere; }
        .viewer-stage { min-height: calc(100vh - 4rem); overflow: auto; padding: 1.25rem; text-align: center; }
        .viewer-stage canvas, .viewer-stage img { max-width: none; background: #fff; box-shadow: 0 2px 12px rgb(0 0 0 / .18); transform-origin: top center; }
        .viewer-status { padding: .35rem 1rem; background: #f9fafb; border-bottom: 1px solid #d1d5db; font-size: .8rem; }
        .viewer-status[data-error=true] { color: #b91c1c; }
        .print-meta { display: none; }
        @media print {
            .viewer-toolbar, .viewer-status, .viewer-stage { display: none !important; }
            .print-meta { display: block; padding: 2rem; }
            .print-meta table { width: 100%; border-collapse: collapse; }
            .print-meta th, .print-meta td { padding: .5rem; border: 1px solid #9ca3af; text-align: left; vertical-align: top; }
        }
    </style>
</head>
<body>
<main data-document-viewer data-url="{{ $mediaUrl }}" data-mime="{{ $media->mime_type }}">
    <nav class="viewer-toolbar" aria-label="Kawalan viewer dokumen">
        <button type="button" onclick="history.back()" title="Kembali" aria-label="Kembali">&#8592;</button>
        <div class="viewer-title">{{ $record->title ?: $media->file_name }}</div>
        @if ($media->mime_type === 'application/pdf')
            <button type="button" data-prev title="Halaman sebelumnya" aria-label="Halaman sebelumnya">&#9664;</button>
            <label>Halaman <input type="number" min="1" value="1" data-page-input aria-label="Nombor halaman"> / <span data-page-count>?</span></label>
            <button type="button" data-next title="Halaman seterusnya" aria-label="Halaman seterusnya">&#9654;</button>
        @endif
        <button type="button" data-zoom-out title="Zum keluar" aria-label="Zum keluar">&#8722;</button>
        <span data-zoom-label>125%</span>
        <button type="button" data-zoom-in title="Zum masuk" aria-label="Zum masuk">&#43;</button>
        @if ($media->mime_type === 'application/pdf')
            <input type="search" data-find-input placeholder="Cari teks" aria-label="Cari teks dalam dokumen">
            <button type="button" data-find>Cari</button>
        @endif
        <button type="button" data-print title="Cetak metadata">Cetak Metadata</button>
        <a href="{{ $downloadUrl }}">Muat Turun</a>
    </nav>
    <div class="viewer-status" data-status role="status">Bersedia.</div>
    <section class="viewer-stage">
        @if ($media->mime_type === 'application/pdf')
            <canvas aria-label="Paparan halaman PDF"></canvas>
        @else
            <img src="{{ $mediaUrl }}" alt="{{ $record->title ?: $media->file_name }}" data-viewer-image>
        @endif
    </section>
</main>
<section class="print-meta">
    <h1>Metadata Rekod</h1>
    <table>
        <tr><th>Tajuk</th><td>{{ $record->title ?: '—' }}</td></tr>
        <tr><th>No. Fail</th><td>{{ $record->registryFile?->file_no ?: '—' }}</td></tr>
        <tr><th>Ruj. Kami</th><td>{{ $record->our_ref ?: '—' }}</td></tr>
        <tr><th>Ruj. Tuan</th><td>{{ $record->their_ref ?: '—' }}</td></tr>
        <tr><th>Jenis</th><td>{{ config("record_types.{$record->record_type}.label", $record->record_type) }}</td></tr>
        <tr><th>Tarikh Rekod</th><td>{{ $record->record_date?->format('d/m/Y') ?: '—' }}</td></tr>
        <tr><th>Pengirim</th><td>{{ collect([$record->sender_name, $record->sender_org])->filter()->join(', ') ?: '—' }}</td></tr>
        <tr><th>Fail Media</th><td>{{ $media->file_name }}</td></tr>
        <tr><th>Dicetak</th><td>{{ now()->format('d/m/Y H:i:s') }}</td></tr>
    </table>
</section>
</body>
</html>
