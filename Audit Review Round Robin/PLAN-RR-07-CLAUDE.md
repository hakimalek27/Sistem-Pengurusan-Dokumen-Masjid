# Pusingan 7 (Pelan) — Claude: Integrasi P6 → pelan v1.3

Tarikh: 2026-08-02 · Asas: `PLAN-RR-06-CODEX.md` ke atas `PELAN-PEMBAIKAN.md` v1.2

## Keputusan

Kedua-dua isu P6 **SAH** (kedua-duanya sisa konsistensi yang saya tercicir semasa edit v1.2 —
saya sahkan lokasi tepat dengan grep sebelum edit) dan **DIBAIKI** dalam v1.3:

| # | Isu P6 | Pembaikan v1.3 |
|---|---|---|
| 1 (P1) | Jadual fail §3.5 masih mengarah "focus trap + `aria-modal`; `clearFocusTrap`" tanpa skop — pelaksana boleh memasang semula trap pada popover utama, bercanggah dengan §3.4 | Baris `help.js` dalam §3.5 ditulis semula: "pengurusan fokus §3.4 (fokus awal + fokus-kembali pada popover utama; **trap + `aria-modal` pada fallback `showUnavailableGuide` SAHAJA**); `clearFocusManagement()`" + rujukan test-hook §3.6 ditambah |
| 2 (P2) | F8 §9 kembali guna laluan pendek `bukti/plan-baseline/manifest.json` | Diselaraskan ke **`Audit Review Round Robin/bukti/plan-baseline/manifest.json`** + nota "jangan cipta folder `bukti/` lain" |

## Imbasan konsistensi tambahan (saya jalankan sendiri selepas edit)

Corak sisa lama dicari secara berprogram — semuanya bersih:
- `clearFocusTrap` = **0** padanan
- Laluan pendek `bukti/plan-baseline` tanpa prefix = **0** (semua rujukan kini berprefix penuh)
- `600ms` = 2 padanan, kedua-duanya rujukan sejarah kepada nilai yang DITOLAK (konteks betul)
- `restart nginx` = **0** (semua kini force-recreate)
- Rujukan "trap … popover utama" yang tinggal = log versi + ujian "tiada trap" (konteks betul)

## Giliran seterusnya — Codex Pusingan 8 (PENGESAHAN TERAKHIR)

Skop paling sempit: sahkan 2 pembaikan v1.3 (§3.5 baris help.js; §9 laluan manifest) + imbasan
pantas tiada kontradiksi silang baharu. Jika bersih → isytihar
**"TIADA PENAMBAHBAIKAN SUBSTANTIF — pelan sedia muktamad"** dan Claude menutup round-robin
(pelan MUKTAMAD v1.3) pada Pusingan 9.
