# F8 — 🔴 TIGA baris jadual §9 saya sendiri TIDAK apple-to-apple

**Tarikh:** 9 Ogos 2026 · **Ditemui oleh:** tentukuran diri semasa round-robin pusingan 1
**Status:** pembetulan sedang diukur (`skrip/ukur-runtime-kohort-f8.mjs`)

## Apa yang berlaku

`metrik-f8.mjs` mengira tiga metrik KANDUNGAN kohort daripada **katalog**:

```
title_equals_instruction   77/124 -> 0/124
title_truncated_mid_word   20/124 -> 0/124
cta_buat_pada_skrin            20 -> 0
```

Saya menerbitkannya dalam `SUSULAN-PEMBAIKAN.md` sebagai ✅. Kemudian saya menjalankan
tentukuran yang pelan sendiri tuntut (§9.3 "apple-to-apple") — **dan alat itu gagal**.

## Tentukuran yang mendedahkannya

Alat yang sama dijalankan pada katalog **commit audit `4e07a70`**:

```
KATALOG PADA 4e07a70 (kohort 25 guide / 124 langkah):
  title == instruction : 0     (asas audit: 77)   ← alat memberi 0 pada KEDUA-DUA belah
  tajuk terpotong      : 0     (asas audit: 20)
  wait_for_user        : 0     (asas CTA: 20)
  placeholder "Langkah N": 118
```

**Alat yang tidak dapat menghasilkan semula angka asas ialah alat yang salah — bukan datanya.**
Itu pelajaran F5 yang saya sendiri rekod dalam memori, dan saya melanggarnya di sini.

## Punca — dan ia menjelaskan ketiga-tiga baris sekali gus

Asas audit ialah ukuran **RUNTIME**, bukan katalog. Pada `4e07a70`, **118/124** tajuk langkah
kohort ialah placeholder `"Langkah N"`, jadi tour **MENERBITKAN** tajuk daripada arahan. Pada
popover, `title == description` untuk 77 langkah. Katalog tidak boleh menunjukkan itu kerana
tajuknya ialah `"Langkah 1"`, `"Langkah 2"`, …

Bagi CTA, puncanya lebih tegas lagi: pada `4e07a70` medan `wait_for_user` **tidak wujud** untuk
kesemua 124 langkah. CTA ditentukan runtime. Jadi metrik saya mengira medan yang **tiada** pada
asas. *(Disahkan bebas oleh Codex pusingan 1: `oldWaitForUser: 0, oldMissing: 124`.)*

## Sisi asas DITENTUKUR TEPAT

Definisi audit dijalankan pada data audit sendiri
(`bukti/pusingan-11-codex/production-desktop-all-tour-steps.json`, 124 langkah):

```
title == description  : 77   ✔ sepadan asas
tajuk terpotong       : 20   ✔ sepadan asas
CTA "Buat pada skrin" : 20   ✔ sepadan asas
```

Ketiga-tiganya dihasilkan semula **tepat**. Maka definisinya betul; hanya SUMBER saya yang
salah. Sisi semasa kini diukur dengan definisi yang sama pada popover sebenar
(`ukur-runtime-kohort-f8.mjs`, desktop 1440×1000, 124 langkah).

## Kesan pada laporan

Sehingga ukuran runtime selesai, ketiga-tiga baris itu dilabel **belum disahkan**, bukan ✅.
Yang KEKAL sah tanpa syarat, kerana ia diukur pada sumber yang betul:

- `placeholder_titles` **258 → 0** — dikira daripada katalog pada kedua-dua belah (medan yang
  sama, definisi yang sama). Tentukuran: katalog `4e07a70` memberi **118** placeholder dalam
  kohort, konsisten dengan 258 pada denominator penuh.
- `action_steps_with_generic_target` 200 → 0, `generic_declared` 443 → 59, `blocked` 0 —
  kesemuanya dari manifest, kedua-dua belah dari sumber yang sama.

## Pelajaran

**Tentukur SETIAP metrik terhadap angka asasnya sebelum menerbitkan pergerakan, bukan hanya
metrik yang anda syak.** Saya menentukur `centerCovered` (dan ia lulus, lalu mendedahkan
penemuan sebenar) tetapi TIDAK menentukur tiga metrik kandungan — kerana ia "kelihatan mudah"
dan jawapannya `0` yang menyenangkan. Nombor yang menyenangkan ialah tempat paling berbahaya
untuk melangkau tentukuran.

🔑 Corak yang boleh diuji: bagi mana-mana metrik "sebelum → selepas", jalankan alat pada
**commit sebelum** dan tuntut ia memberi angka *sebelum*. Jika tidak boleh, alat itu mengukur
perkara lain.
