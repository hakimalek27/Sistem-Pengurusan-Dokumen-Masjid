# INVENTORI F6-W6 (DIJANA — jangan sunting tangan)

Dijana daripada `resources/help/guides.json`, `resources/help/targets.json`
dan `plan-baseline/manifest.json`. Katalog `2026.08.07.1`.

**Guide W6: 3 · langkah: 8 · bersasar generik: 2**

## `public.registration` — Daftar Masjid
route `/daftar` · panel `public` · roles `public`
· 4 langkah · **0 generik**

| # | sasaran | generik | wait | status | shard | registri | tajuk | arahan |
|---:|---|:---:|:---:|---|---|---|---|---|
| 1 | `registration-organisation` | — | ya | specific | tenant-admin-public | active · wizard pendaftaran langkah 1 | Maklumat masjid | Isi nama, negeri, daerah, kod akronim dan slug URL. Kemudian tekan Seterusnya pada borang pendaftaran. |
| 2 | `registration-admin` | — | ya | specific | tenant-admin-public | active · wizard pendaftaran langkah 2 | Pentadbir pertama | Isi nama, e-mel dan nombor WhatsApp pentadbir. Kemudian tekan Seterusnya pada borang pendaftaran. |
| 3 | `registration-consent` | — | ya | specific | tenant-admin-public | active · wizard pendaftaran langkah 3 | Persetujuan | Semak semula maklumat, baca kedua-dua persetujuan dan tandakan kotak hanya jika bersetuju. Tekan Hantar Permohonan pada borang. |
| 4 | `registration-complete` | — | — | specific | tenant-admin-public | active · permohonan berjaya dihantar | Permohonan diterima | Pastikan mesej Permohonan diterima dipaparkan. Permohonan kini menunggu semakan Pentadbir Platform. |

## `public.login` — Log Masuk
route `/log-masuk` · panel `public` · roles `public`
· 2 langkah · **0 generik**

| # | sasaran | generik | wait | status | shard | registri | tajuk | arahan |
|---:|---|:---:|:---:|---|---|---|---|---|
| 1 | `login-identity` | — | — | specific | tenant-admin-public | active · borang belum dihantar | Masukkan identiti | Masukkan e-mel atau nombor telefon berdaftar dalam medan ini. |
| 2 | `login-submit` | — | ya | specific | tenant-admin-public | active · borang belum dihantar | Minta pautan | Tekan Hantar Pautan Log Masuk sekali sahaja, kemudian semak e-mel atau WhatsApp anda. |

## `public.help` — Pusat Bantuan Awam
route `/bantuan` · panel `public` · roles `public`
· 2 langkah · **2 generik**

| # | sasaran | generik | wait | status | shard | registri | tajuk | arahan |
|---:|---|:---:|:---:|---|---|---|---|---|
| 1 | `page-content` | **YA** | — | generic-justified | tenant-admin-public | — | Buka fungsi | Cari panduan pendaftaran dan log masuk atau laporkan masalah. |
| 2 | `page-primary` | **YA** | — | generic-justified | tenant-admin-public | — | Sahkan skop | Pastikan panel, tenant dan role semasa adalah betul. |

## Ringkasan mesin

```json
{
  "guides": 3,
  "steps": 8,
  "generic": 2,
  "generic_keys": [
    "public.help#1",
    "public.help#2"
  ],
  "shards": [
    "tenant-admin-public"
  ],
  "routes": [
    "/daftar",
    "/log-masuk",
    "/bantuan"
  ]
}
```
