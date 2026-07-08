<x-guest-layout title="Diwan — Sistem Pengurusan Dokumen Masjid">
    <div class="card" style="text-align:center;">
        <h2>Selamat datang ke Diwan</h2>
        <p class="muted" style="margin-top:0;">
            Platform pengurusan dokumen digital untuk masjid &amp; surau — registri, klasifikasi fail,
            carian kandungan (OCR), kelulusan elektronik, dan retensi arkib yang teratur.
        </p>
        <div style="display:flex; flex-direction:column; gap:.6rem; margin-top:1.25rem;">
            <a href="{{ url('/daftar') }}" class="btn">Daftar Masjid</a>
            <a href="{{ url('/log-masuk') }}" class="btn btn-ghost">Log Masuk</a>
        </div>
    </div>
    <p class="muted">© {{ date('Y') }} Diwan · Wehdah Solution</p>
</x-guest-layout>
