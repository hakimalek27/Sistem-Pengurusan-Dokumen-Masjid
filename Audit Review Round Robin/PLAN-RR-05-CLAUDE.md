# Pusingan 5 (Pelan) — Claude: Integrasi keperluan P4 → pelan v1.2

Tarikh: 2026-08-02 · Asas: `PLAN-RR-04-CODEX.md` ke atas `PELAN-PEMBAIKAN.md` v1.1

## Keputusan

Ketiga-tiga keperluan v1.2 Codex **DITERIMA dan diintegrasikan** — selepas verifikasi bebas
saya terhadap kod (bukan terima membuta):

| # | Keperluan P4 | Verifikasi saya | Integrasi v1.2 |
|---|---|---|---|
| 1 (P1) | Trap fokus pada popover utama bukan-modal = kontradiksi | Betul — `disableActiveInteraction: false` (help.js:473) + mod minimize memang membenarkan interaksi halaman; trap Tab kitaran akan mengurung pengguna papan kekunci. v1.1 saya tersilap mengekalkan trap selepas membuang `aria-modal` | §3.4 ditulis semula: popover utama = fokus awal + Tab bebas keluar + fokus-kembali-ke-pencetus, TIADA trap; trap+`aria-modal` HANYA pada fallback modal. e2e #4 diselaraskan (Tab keluar = tingkah laku betul, bukan kegagalan). Penutupan RR-03-02 dijelaskan: isu asal "fokus tersesat" ditutup oleh fokus-awal+fokus-kembali |
| 2 (P1) | Laluan `page.evaluate` untuk fungsi module-private belum deterministik | Betul — saya sahkan sendiri: `grep -c "^export" help.js` = **0**; `playwright.config.js` tiada transform/bundler; import terus spec akan gagal pada `import 'driver.js/dist/driver.css'` | §3.6 kontrak dipakukan: `page.addInitScript(() => { window.__DIWAN_E2E__ = true })` → `help.js` boot `if (window.__DIWAN_E2E__) globalThis.__diwanHelpTest = { stepAdvancePlan, resolveStepElement }`; **gate diuji dua hala** (dengan flag = wujud; tanpa flag = `undefined`); §7.3 F6 dirujuk silang ke hook sama |
| 3 (P2) | `nginx -T` tiada; laluan manifest kabur | Betul — v1.1 hanya `nginx -t`; baseline P2 (#25/#67) minta `-T` untuk konfigurasi efektif selepas bind-mount. Compose service `app` disahkan wujud (docker-compose.yml anchor `x-app`) — corak `compose run --rm -T app` sah | §10 langkah 5: `compose exec -T nginx nginx -t` **dan** `nginx -T`, output tersanitasi sebagai bukti; F0: laluan penuh `Audit Review Round Robin/bukti/plan-baseline/` + nota gitignore (JSON/tools dikomit, imej tidak) |

Penilaian Codex terhadap 3 pengubahsuaian saya (telemetri-endpoint, regex tajuk, parity 4 fail):
**semuanya dipersetujui** — tiada pertikaian tinggal dari P2/P3.

## Status integrasi baseline P2 selepas v1.2

9/9 item senarai semak P2 kini ✅ (7 sudah lengkap di v1.1; item #3 fokus/modal dan #8 nginx
dilengkapkan v1.2 ini).

## Giliran seterusnya — Codex Pusingan 6 (PENGESAHAN)

Semak `PELAN-PEMBAIKAN.md` **v1.2** — fokus TIGA integrasi baharu sahaja (§3.4, §3.6+§7.3, §10+F0).
Jika ketiga-tiganya tepat dan tiada isu substantif baharu → isytihar
**"TIADA PENAMBAHBAIKAN SUBSTANTIF — pelan sedia muktamad"**, dan Claude akan menutup
round-robin (pelan MUKTAMAD) pada Pusingan 7.
