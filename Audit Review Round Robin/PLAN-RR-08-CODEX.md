# Pusingan 8 (Pelan) — Codex: Pengesahan terakhir v1.3

Tarikh: 2026-08-02 · Asas kod: commit `8342d95`

## Semakan pembaikan 1 (seksyen 3.5) — LULUS

Jadual fail kini menyatakan pengurusan fokus §3.4 dengan fokus awal dan fokus-kembali pada
popover utama, manakala trap + `aria-modal` dikhususkan kepada fallback
`showUnavailableGuide` **SAHAJA**, serta menggunakan nama pembersihan
`clearFocusManagement()` (`PELAN-PEMBAIKAN.md:419-424`). Ini selaras dengan kontrak §3.4:
popover utama bukan-modal tidak memasang `aria-modal` dan tidak memerangkap fokus
(`PELAN-PEMBAIKAN.md:393-409`); hanya fallback benar-benar modal menerima kedua-duanya dan
listener dibersihkan melalui `clearFocusManagement()` (`PELAN-PEMBAIKAN.md:410-415`). Ujian
yang dirancang turut membezakan kedua-dua keadaan (`PELAN-PEMBAIKAN.md:450-456`).

## Semakan pembaikan 2 (seksyen 9) — LULUS

Disiplin pengukuran F8 kini memakukan manifest pada laluan penuh
`Audit Review Round Robin/bukti/plan-baseline/manifest.json`, melarang penciptaan folder
`bukti/` lain, dan mengikat F8 kepada set yang sama (`PELAN-PEMBAIKAN.md:1031-1036`). Kontrak
ini sama dengan F0, yang memberikan laluan penuh manifest dan laluan penuh tools
(`PELAN-PEMBAIKAN.md:130-138`).

## Imbasan terakhir

- Tiada penggunaan `clearFocusTrap`; nama kontrak yang tinggal ialah
  `clearFocusManagement()` (`PELAN-PEMBAIKAN.md:413-415,423`).
- Tiada rujukan pendek `bukti/plan-baseline` dan tiada arahan `restart nginx`; oleh itu dua
  pembaikan tidak memperkenalkan kontradiksi silang baharu.
- Singkatan umum ``dibekukan dalam `bukti/` F0`` di huraian denominator
  (`PELAN-PEMBAIKAN.md:854-859`) bukan laluan manifest alternatif: ia menunjuk kembali kepada
  F0, yang sudah memakukan laluan penuh (`PELAN-PEMBAIKAN.md:130-138`). Ia tidak mengatasi
  larangan eksplisit F8 (`PELAN-PEMBAIKAN.md:1031-1036`) dan tidak memerlukan versi baharu.

## KEPUTUSAN: (a) TIADA PENAMBAHBAIKAN SUBSTANTIF — pelan sedia muktamad
