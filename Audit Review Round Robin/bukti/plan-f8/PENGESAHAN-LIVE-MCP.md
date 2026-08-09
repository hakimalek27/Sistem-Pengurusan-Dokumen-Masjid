# F8 — pengesahan LANGSUNG pada produksi melalui MCP Chrome (9 Ogos 2026)

**Sasaran:** `bakwim.my` (produksi, Deploy 14 `2325bec`) · viewport diukur **1568×726**
**Kaedah:** `javascript_tool` MCP terhadap DOM hidup. Read-only; tiada borang dihantar, tiada
data diubah, tiada kredensial ditaip.

## 1. Keupayaan MCP DIUKUR SEMULA — dan penilaian lama saya perlu dipinda

Awal sesi saya merekod bahawa MCP Chrome "tidak boleh melayani tujuan ini" berdasarkan tiga
probe. Diukur semula hari ini pada tab baharu DAN tab sedia ada:

| Keupayaan | Awal sesi | Diukur semula |
|---|---|---|
| `document.visibilityState` | `hidden` | `hidden` (tidak berubah) |
| `innerWidth × innerHeight` | — | **1568×726** (nyata) |
| `outerWidth × outerHeight` | `0x0` | `160x28` (masih tidak bermakna) |
| **JavaScript / baca DOM** | tidak diuji berasingan | ✅ **BERFUNGSI** |
| `Page.captureScreenshot` | CDP timeout 30s | ❌ **masih** CDP timeout 30s |

⭐ **Pindaan:** pernyataan "MCP tidak boleh digunakan" terlalu luas. Yang benar: **skrinsyot
mati, tetapi pengesahan DOM langsung BERFUNGSI** — dan itu memadai untuk mengesahkan fakta
struktur pada sistem hidup. Larian JS pada tab yang baru dinavigasi juga boleh tamat masa;
tab yang sudah dimuatkan berfungsi.

⚠️ **Had yang kekal, dan ia penting:** tab MCP ialah `visibilityState: hidden`, jadi pemasa
beku — keadaan runtime tour (sorotan `.driver-active-element`) **tidak boleh dipercayai** di
sini. Diukur pada produksi: popover WUJUD (368×222 @ 263,322) tetapi `.driver-active-element`
ialah `null`. Itu tandatangan penemuan **#73**, yang sudah ditutup sebagai artifak tab latar —
jadi ia **TIDAK** dilaporkan sebagai kecacatan. Guna Playwright untuk apa-apa yang bergantung
pemasa.

## 2. Yang DISAHKAN hidup pada produksi

```
LIVE                          : bakwim.my
hutang F7 `help-search-form`  : ADA                    ✔
`#help-query`                 : ADA                    ✔
`.diwan-help-search-status`   : ADA                    ✔
sasaran bantuan               : help-launcher · help-center · help-search · help-scope ·
                                help-search-form · help-diagnosis · help-support
GET /bantuan/imej/public.help         -> 200           ✔
GET /bantuan/imej/public.registration -> 200           ✔
```

### Tiga perkara yang ini sahkan

1. **Hutang F7 (tugasan #74) HIDUP di produksi.** `help-search-form` hadir pada DOM sebenar,
   bukan hanya dalam katalog. `help-search` turut hadir dan itu BUKAN percanggahan — ia sasaran
   registri yang berasingan (seksyen carian lawan borang carian); yang berubah ialah langkah
   katalog `*.bantuan#1` kini menunjuk kepada borang.
2. **Gate carian yang saya baiki bersandar pada pemilih yang WUJUD di produksi.**
   `#help-query` dan `.diwan-help-search-status` kedua-duanya hadir, jadi
   `assertCarianBantuan()` tidak akan gagal atas sebab pemilih apabila larian §9.1a dijalankan.
3. **Dakwaan "500 pada `/bantuan/imej`" yang saya TARIK kini disahkan pada produksi.**
   Kedua-dua endpoint memberi **200** pada sistem hidup. Ia memang artifak kunci SQLite
   tempatan, bukan kecacatan produk — dan kini itu dibuktikan pada produksi, bukan hanya
   disimpulkan daripada `curl` tempatan.

## 3. Apa yang MASIH tidak boleh disahkan tanpa kredensial

Halaman panel tenant/admin memerlukan sesi. Semua di atas ialah laluan AWAM. Matriks §9.1a
penuh (10 identiti × 2 viewport) kekal ⏸ sehingga pemilik membekalkan kredensial superadmin —
itu had kebenaran, bukan had alat.
