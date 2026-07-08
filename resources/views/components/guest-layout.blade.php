@props(['title' => null])
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Diwan — Sistem Pengurusan Dokumen Masjid' }}</title>
    <style>
        :root { --hijau:#047857; --hijau-t:#065f46; --abu:#6b7280; --bg:#f3f4f6; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:#111827; line-height:1.5; }
        .wrap { max-width:560px; margin:0 auto; padding:2rem 1rem; }
        .brand { text-align:center; margin-bottom:1.5rem; }
        .brand h1 { color:var(--hijau); font-size:1.75rem; margin:.25rem 0; }
        .brand p { color:var(--abu); margin:0; }
        .card { background:#fff; border-radius:.75rem; box-shadow:0 1px 3px rgba(0,0,0,.1); padding:1.5rem; }
        h2 { margin-top:0; font-size:1.25rem; }
        label { display:block; font-weight:600; font-size:.9rem; margin:.75rem 0 .25rem; }
        input[type=text], input[type=email], select { width:100%; padding:.55rem .7rem; border:1px solid #d1d5db; border-radius:.5rem; font-size:1rem; }
        .check { display:flex; gap:.5rem; align-items:flex-start; margin:.75rem 0; font-weight:400; font-size:.9rem; }
        .check input { margin-top:.2rem; }
        .btn { display:inline-block; width:100%; text-align:center; background:var(--hijau); color:#fff; border:0; border-radius:.5rem; padding:.7rem 1rem; font-size:1rem; font-weight:600; cursor:pointer; text-decoration:none; }
        .btn:hover { background:var(--hijau-t); }
        .btn-ghost { background:transparent; color:var(--hijau); border:1px solid var(--hijau); }
        .err { color:#b91c1c; font-size:.85rem; margin-top:.25rem; }
        .ok { background:#ecfdf5; border:1px solid #a7f3d0; color:var(--hijau-t); padding:1rem; border-radius:.5rem; }
        .muted { color:var(--abu); font-size:.85rem; text-align:center; margin-top:1rem; }
        a { color:var(--hijau); }
    </style>
    @livewireStyles
</head>
<body>
    <div class="wrap">
        <div class="brand">
            <h1>ﺍﻟﺪﻳﻮﺍﻥ · Diwan</h1>
            <p>Sistem Pengurusan Dokumen Masjid</p>
        </div>
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
