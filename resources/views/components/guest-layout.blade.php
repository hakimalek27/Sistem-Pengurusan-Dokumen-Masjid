@props(['title' => null])
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Diwan — Sistem Pengurusan Dokumen Masjid' }}</title>
    <style>
        :root {
            --hijau:#047857;
            --hijau-t:#065f46;
            --dakwat:#17211d;
            --abu:#64706b;
            --garis:#dbe5df;
            --bg:#f6f8f6;
            --panel:#ffffff;
        }
        * { box-sizing:border-box; }
        body { margin:0; font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--dakwat); line-height:1.5; }
        .wrap { width:min(1040px, 100%); margin:0 auto; padding:2rem 1rem; }
        .brand { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; }
        .brand-mark { min-width:0; }
        .brand h1 { color:var(--hijau); font-size:1.6rem; margin:.25rem 0; letter-spacing:0; }
        .brand p { color:var(--abu); margin:0; }
        .brand-actions { display:flex; gap:.5rem; flex-wrap:wrap; justify-content:flex-end; }
        .brand-actions a { border:1px solid var(--garis); border-radius:.5rem; color:var(--dakwat); padding:.45rem .7rem; text-decoration:none; font-size:.9rem; font-weight:650; background:#fff; }
        .brand-actions a:hover { border-color:var(--hijau); color:var(--hijau); }
        .brand-actions .diwan-help-launcher-button { min-height:2.2rem; padding:.45rem .7rem; border-radius:.5rem; font-size:.9rem; }
        .card { background:var(--panel); border:1px solid var(--garis); border-radius:.5rem; box-shadow:0 10px 30px rgba(23,33,29,.06); padding:1.5rem; max-width:560px; margin:0 auto; }
        h2 { margin-top:0; font-size:1.25rem; }
        label { display:block; font-weight:650; font-size:.9rem; margin:.75rem 0 .25rem; }
        input[type=text], input[type=email], select { width:100%; padding:.62rem .75rem; border:1px solid #cfd9d3; border-radius:.45rem; font-size:1rem; background:#fff; }
        input:focus, select:focus { outline:2px solid rgba(4,120,87,.18); border-color:var(--hijau); }
        .check { display:flex; gap:.5rem; align-items:flex-start; margin:.75rem 0; font-weight:400; font-size:.9rem; }
        .check input { margin-top:.2rem; }
        .btn { display:inline-block; width:100%; text-align:center; background:var(--hijau); color:#fff; border:0; border-radius:.45rem; padding:.72rem 1rem; font-size:1rem; font-weight:700; cursor:pointer; text-decoration:none; }
        .btn:hover { background:var(--hijau-t); }
        .btn-ghost { background:transparent; color:var(--hijau); border:1px solid var(--hijau); }
        .registration-steps { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.4rem; list-style:none; padding:0; margin:0 0 1rem; }
        .registration-steps li { display:flex; align-items:center; gap:.4rem; color:var(--abu); font-size:.78rem; min-width:0; }
        .registration-steps li::after { content:""; height:1px; background:var(--garis); flex:1; }
        .registration-steps li:last-child::after { display:none; }
        .registration-steps b { display:grid; place-items:center; width:1.55rem; height:1.55rem; border-radius:999px; border:1px solid var(--garis); background:#fff; flex:0 0 auto; }
        .registration-steps .active { color:var(--hijau); font-weight:700; }
        .registration-steps .active b { background:var(--hijau); color:#fff; border-color:var(--hijau); }
        .registration-actions { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.55rem; margin-top:1rem; }
        .registration-actions .btn:only-child { grid-column:2; }
        .registration-review { display:grid; gap:.2rem; border-left:3px solid var(--hijau); padding:.7rem .8rem; margin-bottom:1rem; background:#f2faf6; font-size:.88rem; }
        .err { color:#b91c1c; font-size:.85rem; margin-top:.25rem; }
        .ok { background:#ecfdf5; border:1px solid #a7f3d0; color:var(--hijau-t); padding:1rem; border-radius:.5rem; }
        .muted { color:var(--abu); font-size:.85rem; text-align:center; margin-top:1rem; }
        a { color:var(--hijau); }
        .home-shell { display:grid; grid-template-columns:minmax(0,1.05fr) minmax(280px,.95fr); gap:1.25rem; align-items:stretch; }
        .home-hero { min-height:430px; border-radius:.5rem; overflow:hidden; border:1px solid var(--garis); background:
            linear-gradient(rgba(8,28,22,.74), rgba(8,28,22,.62)),
            url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1200' height='800' viewBox='0 0 1200 800'%3E%3Crect width='1200' height='800' fill='%23dfe8e2'/%3E%3Cpath d='M0 590h1200v210H0z' fill='%23f4f0e7'/%3E%3Cpath d='M120 570h960v40H120z' fill='%238b7d68'/%3E%3Cpath d='M210 300h780v270H210z' fill='%23ffffff'/%3E%3Cpath d='M240 330h210v210H240zM495 330h210v210H495zM750 330h210v210H750z' fill='%23e8efe9'/%3E%3Cpath d='M520 300c0-74 160-74 160 0z' fill='%23047857'/%3E%3Cpath d='M190 300h820v24H190z' fill='%23065f46'/%3E%3Cpath d='M160 245h880v55H160z' fill='%23fbfbf8'/%3E%3Cpath d='M280 245c70-110 570-110 640 0z' fill='%23fbfbf8'/%3E%3Cpath d='M600 140v26' stroke='%23047857' stroke-width='9' stroke-linecap='round'/%3E%3Ccircle cx='600' cy='116' r='25' fill='%23047857'/%3E%3Ccircle cx='612' cy='109' r='20' fill='%23dfe8e2'/%3E%3C/svg%3E") center/cover;
            color:#fff; display:flex; align-items:flex-end; }
        .home-hero-inner { padding:2rem; max-width:640px; }
        .home-eyebrow { margin:0 0 .5rem; font-size:.88rem; color:#cde9dc; font-weight:750; text-transform:uppercase; }
        .home-title { margin:0; font-size:clamp(2.1rem, 7vw, 4.25rem); line-height:1.02; letter-spacing:0; }
        .home-copy { margin:1rem 0 0; color:#eef8f2; max-width:39rem; font-size:1.05rem; }
        .home-panel { display:grid; gap:1rem; }
        .quick-card { background:#fff; border:1px solid var(--garis); border-radius:.5rem; padding:1.15rem; }
        .quick-card h2, .quick-card h3 { margin:.1rem 0 .45rem; }
        .quick-card p { margin:.25rem 0; color:var(--abu); }
        .metric-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:.7rem; }
        .metric { border:1px solid var(--garis); border-radius:.45rem; padding:.8rem; background:#fbfdfb; }
        .metric strong { display:block; font-size:1.05rem; }
        .metric span { color:var(--abu); font-size:.82rem; }
        .workflow-list { display:grid; gap:.55rem; margin-top:.65rem; }
        .workflow-step { display:flex; gap:.6rem; align-items:flex-start; color:#33433c; }
        .workflow-step b { display:grid; place-items:center; flex:0 0 1.55rem; height:1.55rem; border-radius:999px; background:#e7f5ed; color:var(--hijau); font-size:.8rem; }
        .footer-note { text-align:center; color:var(--abu); font-size:.82rem; margin:1.5rem 0 0; }
        @media (max-width: 760px) {
            .wrap { padding:1rem; }
            .brand { align-items:flex-start; flex-direction:column; }
            .brand-actions { justify-content:flex-start; width:100%; }
            .home-shell { grid-template-columns:1fr; }
            .home-hero { min-height:360px; }
            .home-hero-inner { padding:1.25rem; }
            .metric-grid { grid-template-columns:1fr; }
        }
    </style>
    @livewireStyles
    @vite('resources/js/help.js')
</head>
<body>
    <div class="wrap">
        <div class="brand">
            <div class="brand-mark">
                <h1>ﺍﻟﺪﻳﻮﺍﻥ · Diwan</h1>
                <p>Sistem Pengurusan Dokumen Masjid</p>
            </div>
            <nav class="brand-actions" aria-label="Navigasi utama">
                <a href="{{ url('/') }}">Utama</a>
                <a href="{{ url('/log-masuk') }}">Log Masuk</a>
                <a href="{{ url('/daftar') }}">Daftar</a>
                <livewire:help-launcher panel="public" />
            </nav>
        </div>
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
