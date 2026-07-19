<x-guest-layout title="Diwan — Sistem Pengurusan Dokumen Masjid">
    <section class="home-shell" aria-label="Diwan SPDM">
        <div class="home-hero">
            <div class="home-hero-inner">
                <p class="home-eyebrow">SaaS multi-tenant untuk pejabat masjid</p>
                <h2 class="home-title">Diwan</h2>
                <p class="home-copy">
                    Peti Masuk, klasifikasi fail, minit tindakan, carian OCR, kelulusan dan retensi
                    dalam satu ruang kerja yang terasing mengikut masjid.
                </p>
            </div>
        </div>

        <div class="home-panel">
            <div class="quick-card">
                <h2>Akses Sistem</h2>
                <p>Masuk ke panel masjid atau hantar permohonan tenant baharu.</p>
                <div style="display:grid; gap:.65rem; margin-top:1rem;">
                    <a href="{{ url('/log-masuk') }}" class="btn">Log Masuk</a>
                    <a href="{{ url('/daftar') }}" class="btn btn-ghost">Daftar Masjid</a>
                </div>
            </div>

            <div class="metric-grid" aria-label="Kawalan utama">
                <div class="metric">
                    <strong>Tenant</strong>
                    <span>Data masjid dipisahkan melalui skop tenant.</span>
                </div>
                <div class="metric">
                    <strong>Dokumen</strong>
                    <span>UI, e-mel dan WhatsApp masuk ke Peti Masuk.</span>
                </div>
                <div class="metric">
                    <strong>Minit</strong>
                    <span>Tindakan dan s.k. dihantar kepada ahli berkaitan.</span>
                </div>
                <div class="metric">
                    <strong>Retensi</strong>
                    <span>Notis, eksport dan pegangan sebelum pelupusan.</span>
                </div>
            </div>

            <div class="quick-card">
                <h3>Aliran Pejabat</h3>
                <div class="workflow-list">
                    <div class="workflow-step"><b>1</b><span>Dokumen diterima dan disahkan format/kuota.</span></div>
                    <div class="workflow-step"><b>2</b><span>Kerani klasifikasikan ke fail dan nombor rujukan.</span></div>
                    <div class="workflow-step"><b>3</b><span>Minit tindakan, kelulusan dan audit kekal direkod.</span></div>
                </div>
            </div>
        </div>
    </section>

    <p class="footer-note">&copy; {{ date('Y') }} Diwan · Wehdah Solution</p>
</x-guest-layout>
