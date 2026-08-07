# Penemuan #73 — "sorotan tour hilang pada produksi" ialah ARTIFAK PENGUKURAN

**Tarikh:** 8 Ogos 2026 · **Kod diukur:** `ad7c1b9` (bundel `help-EPOANIj9.js`)
**Keputusan:** BUKAN kecacatan produk. Pemulihan sorotan W5d berfungsi pada produksi, pada
halaman awam DAN pada senario tenant sebenar. Bacaan saya pada Deploy 13 dibuat dalam tab
yang tidak pernah berada di hadapan, dan Chrome **membekukan pemasa** dalam tab sebegitu.

## Mengapa dakwaan asal boleh dipercayai — dan mengapa ia salah

Laporan Deploy 13 mencatat: muatan segar menyorot betul, "beberapa saat kemudian" `aktif = null`
sedangkan sasaran masih dalam DOM dan popover masih "1 daripada 2". Itu tandatangan W5d yang
tepat, jadi ia kelihatan seperti kecacatan yang berulang.

Yang tidak saya catat ialah **di mana** bacaan itu dibuat: tab MCP yang dicipta secara
pengaturcaraan dan tidak pernah dibawa ke hadapan. Diukur terus dalam tab itu:

```
visibilityState : "hidden"      hasFocus: false
interval 250ms  : 2 detik dalam 3000ms   (detik pertama pada 2669ms)
```

Tinjauan pemulihan `watchHighlightLoss` berjalan pada 250ms. Dalam tab yang dibekukan ia tidak
boleh menembak, jadi sorotan yang dipadam morph kekal padam **selagi tiada siapa melihat tab
itu**. Sebaik tab dilihat, pembaikan berlaku serta-merta.

## Sepuluh pengukuran

Kesemuanya pada bundel yang sama; hash aset tempatan **`help-EPOANIj9.js` identik dengan
produksi**, jadi larian tempatan menguji kod yang benar-benar hidup di bakwim.my.

| # | Persekitaran | Perlumbaan | Hilang → pulih | Keadaan akhir |
|---|---|---|---|---|
| 1 | tempatan `/bantuan` | morph selepas sorotan | 14625 → 14688ms (63ms) | ✅ disorot |
| 2 | produksi `/bantuan` bersih | morph **sebelum** sorotan | tiada kehilangan | ✅ disorot (60s) |
| 3 | produksi, cpu 6× + 1200ms | morph selepas sorotan | 3165 → 4397ms | ✅ disorot (60s) |
| 4 | produksi, cpu 20× + 3000ms | morph selepas sorotan | 10136 → 10313ms (177ms) | ✅ disorot (75s) |
| 5–7 | produksi, cpu 8× + 1500ms ×3 | identik ketiga-tiga | ~3.9s → 40–270ms | ✅ disorot |
| 8 | Chrome sebenar, tab **latar** | pemasa beku | tidak pernah pulih | 🔴 tiada sorotan |
| 9 | headed, latar → dibawa ke hadapan | beku 25s | pulih **26ms** selepas aktif | ✅ disorot |
| 10 | **tenant** `/app/mam/bantuan` | morph selepas sorotan | 6044 → 6183ms (139ms) | ✅ disorot (30s) |

Larian 9 ialah yang menentukan. Garis masa merakam detak pemasa sendiri:

```
0 → 25s   SIFAR detak (interval 250ms tidak berjalan langsung)
25.2s     tab dibawa ke hadapan — semuanya cair serentak:
          commit-respons → morphed → -sorotan → +sorotan (26ms) → detak 1s normal
```

Pengguna sebenar melihat tab yang dipandangnya. Dalam setiap keadaan itu sorotan ada.

## Larian 10 menutup jurang yang terbuka sejak W5

Pengesahan tenant tidak pernah dibuat kerana kredensial produksi tidak pernah dicipta. Senario
W5d yang SEBENAR — sasaran di dalam komponen Livewire yang memorph — kini diukur pada sesi
tenant tempatan dengan bundel yang identik: hilang pada 6044ms, kembali 139ms kemudian, kekal.

## Baki yang JUJUR — dua perkara yang saya tidak tentukan

1. Dalam tab latar yang sudah terbuka ~342 saat, bukan sahaja kelas hilang tetapi **overlay
   Driver.js juga tiada** (`adaOverlay: false`) sedangkan popover kekal. Saya tidak menentukan
   sebabnya. Ia tidak berlaku dalam tab yang di hadapan pada mana-mana sepuluh larian.
2. Sama ada Deploy 12 berkelakuan sama kekal tidak diketahui, dan kini tidak relevan — tiada
   kecacatan untuk diatribusikan kepada mana-mana deploy.

## Hutang F7 yang larian 10 sahkan dengan angka

`tenant.bantuan#1` menyorot `help-search`: **1041×3186 px = 70% tinggi `<main>`**. Itu tepat
kecacatan makna yang W5 §17 rekod. `help-search-form` (1008×70) wujud sejak W6.

## Pelajaran — dan ia BUKAN pelajaran baharu

F6-W1 sudah merekodkan: **jangan assert pada keadaan sementara, rakam urutannya.** Saya
melanggarnya di sini dengan mengambil dua sampel titik dan menyimpulkan keadaan kekal.

Yang baharu ialah tambahannya: **sahkan bahawa persekitaran pengukuran boleh memerhati apa yang
anda ukur.** Tab latar membekukan pemasa; mana-mana pembaikan berasaskan `setInterval`/
`setTimeout` akan kelihatan mati di sana walaupun ia sihat sepenuhnya. Sebelum melaporkan
kecacatan masa-nyata daripada pelayar automatik, ukur `document.visibilityState` dan buktikan
pemasa berdetik.

## Cara menghasilkan semula

```
scratchpad/rakam-sorotan.mjs         [tempoh_ms]      # E2E_BASE_URL, THROTTLE_CPU, LATENCY_MS
scratchpad/garis-masa-visibility.mjs                  # latar -> hadapan, garis masa penuh
scratchpad/rakam-tenant.mjs          [tempoh_ms]      # senario W5d sebenar, perlu sesi tenant
```
Skrip disalin ke `skrip/` di sebelah dokumen ini supaya ia tidak hilang bersama scratchpad.
