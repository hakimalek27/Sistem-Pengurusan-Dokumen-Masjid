# KEPUTUSAN PEMILIK — Pelan Pembaikan Diwan (SPDM)

**Tarikh diterima:** 2 Ogos 2026 (melalui sesi Claude, arahan terus pemilik)
**Konteks:** jawapan diberikan terhadap **senarai asal D1–D7** (v1.3). Jadual keputusan pelan
telah berkembang kepada **D1–D11** dalam v1.4–v1.6 — pemetaan ke jadual semasa di bawah.
**Status pelan semasa jawapan diterima:** v1.6 separa, round-robin BELUM muktamad
(giliran P17 menunggu — lihat `PLAN-RR-STATUS.md`).

## Jawapan verbatim pemilik

> "d1, ya. d2 ya. d3 kekal. d4 ya. d5 ya. d6 terima. d7 gabung."

## Pemetaan ke jadual D semasa (v1.6 §11)

| # | Jawapan pemilik | Tafsiran terhadap jadual v1.6 | Kesan |
|---|---|---|---|
| D1 | **Ya** | Default borang peraturan retensi → `semak` | F4 (L1) boleh dilaksana |
| D2 | **Ya** | Pemilik MAHU `auto_disposal_enabled=false` untuk masjid baharu. ⚠️ v1.4 menaikkan taraf D2: ia perubahan PRODUK bercanggah `DIWAN-SPEC.md:470` → **memerlukan D10 (Addendum spec v2.6) diluluskan dahulu**. Jawapan "ya" ini menyatakan HASRAT; **kelulusan addendum eksplisit masih diperlukan** — draf `# ADDENDUM v2.6` perlu dibentang kepada pemilik sebelum L2 ditulis (gate §5.3 kekal) | F4 (L2) menunggu D10 |
| D3 | **Kekal** | 14 peraturan platform produksi kekal `auto_padam` (patuh §16.1) | Tiada perubahan data produksi |
| D4 | **Ya** | Pengesahan-kedua dengan kiraan rekod terjejas | F4 |
| D5 | **Ya** | Meliputi kedua-dua bahagian (pemilik ialah pemilik polisi `CLAUDE.md`): **(a)** pengecualian polisi bertulis untuk `axe-core` (dev-only) DILULUSKAN — mesti direkodkan dalam dokumen kawalan repo semasa pelaksanaan F7; **(b)** `package.json` + lockfile boleh diubah untuk menambahnya | F7/F8 |
| D6 | **Terima** | Bump `version` per-guide yang diubah → auto-tour semula sekali untuk pengguna sedia ada | F5/F6 |
| D7 | **Gabung** | Deploy DIGABUNG (§10 pilihan gabungan, cth. F1+F2, F5+F7) — ⚠️ berbeza daripada cadangan ("berasingan F1"); trade-off bisect regresi lebih kasar **diterima pemilik secara sedar** | Semua fasa |

## ✅ Keputusan susulan diterima (2 Ogos 2026, arahan kedua pemilik)

**Jawapan verbatim pemilik:** *"d8 d9 d11 ikut cadangan, d10 lulus"*

| # | Jawapan | Tafsiran (ikut cadangan pelan v1.9) | Kesan |
|---|---|---|---|
| **D8** | **Ikut cadangan** | (i) **Ya** — prune token `used`/`expired` >30 hari via `diwan:prune-logs`; (ii) **berhenti selepas 7 hari + satu eskalasi kepada admin** untuk peringatan minit harian | F10 |
| **D9** | **Ikut cadangan** | **Ya** — `ARG GIT_SHA` + `LABEL org.opencontainers.image.revision` dalam `docker/Dockerfile` | Bukti deploy |
| **D10** | **LULUS** | Pemilik **meluluskan Addendum spec v2.6** (tukar lalai `auto_disposal_enabled` → `false` untuk masjid baharu + selaraskan teks pengakuan §16.2). Kelulusan eksplisit diberikan terhadap kandungan yang dinyatakan dalam soalan D10 → **D2 tidak lagi tersekat**; teks penuh `# ADDENDUM v2.6` ditulis ke `DIWAN-SPEC-ADDENDUM-2026-07.md` pada permulaan pelaksanaan F0/F4 (bukan sekarang — sekatan fail fasa pelan) | F4 (L2) dibuka |
| **D11** | **Ikut cadangan** | **Luluskan semua** — 16 fail repo + 1 artifak audit (senarai stabil v1.8→v1.9) sebagai perkakas pengukuran F0 | F0/F6/F8 dibuka |

**Kesemua D1–D11 kini DIJAWAB.** Prasyarat keputusan F0 lengkap.

## Nota penting (dikemas selepas penutupan)

1. 🏁 **Round-robin SELESAI — `PELAN-PEMBAIKAN.md` v1.11 MUKTAMAD** (P27, 2 Ogos 08:43;
   syarat #6 dipenuhi oleh Codex P26 — pusingan pertama tanpa pindaan substantif). Semua
   keputusan D1–D11 dalam fail ini kini termaktub dalam pelan muktamad (§11 + `KIRAAN
   DINORMALISASI`: D11 = 16 entri = 19 fail repo + 1 bundle audit).
2. **Pelaksanaan F0–F10 menunggu SATU perkara: arahan mula pemilik** (mengangkat sekatan
   "jangan ubah kod"). Tiada keputusan tertunggak.
3. Baki tindakan pemilik bukan-keputusan: (a) disyorkan komit/snapshot folder perancangan
   (§0.7 #2) sebelum pelaksanaan; (b) Lampiran A1 — padam tiket ujian `SUP-260801-HXQ0DIOL`.
