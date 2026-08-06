# INVENTORI F6-W5 — shard `tenant-admin-public`

Ditulis **sebelum** sebarang kod W5, seperti W2/W3/W4. Semua angka DIUKUR daripada
`manifest.json` + `guides.json` + `targets.json`, bukan daripada jadual beku pelan.

Asas: `local = origin = server = cea55da` (Deploy 10, F6-W4 LIVE).

---

## 1. Skop

```
guide W5   : 35        langkah: 146        generik: 144
family     : tenant 112 · admin 32
wait_for_user : false 144/144   (KESEMUANYA langkah PENERANGAN)
viewport      : desktop 144/144
```

W5 ialah **wave TERBESAR yang tinggal** dan — menurut §7.3 — satu-satunya yang menggerakkan
metrik **KOHORT 25/124**. Kohort itu `tenant` sepenuhnya, jadi ia tidak bergerak langsung
sepanjang W1–W4 dan mula bergerak di sini.

Seperti W4, kesemua langkah `wait_for_user: false`; metrik keutamaan
`action_steps_with_generic_target` sudah **0** dan W5 tidak menyentuhnya.

---

## 2. ⭐ Kelebihan besar: kebanyakan route SUDAH ada sasaran

W1–W4 sudah membina registri untuk hampir semua skrin tenant. Beban W5 sebahagian besarnya
ialah **memetakan**, bukan menulis atribut Blade baharu.

| Route | Langkah W5 | Sasaran aktif sedia ada |
|---|---|---|
| `/records` | 5 | **30** |
| `/registry-files` | 6 | **21** |
| `/peti-masuk` | 6 | 13 |
| `/minit-saya` | 6 | 11 |
| `/log-aktiviti` | 5 | 7 |
| `/profil` | 6 | 7 |
| `/kelulusan` | 6 | 6 |
| `/carian` · `/persediaan` · `/ahli-peranan` · `/tetapan-masjid` · `/penggunaan` | 5–7 setiap satu | 5 setiap satu |
| `/retensi` · `/pembetulan-rekod` | 5 setiap satu | 3 setiap satu |
| `/sensitive-access-logs` · `/laporan` | 4 setiap satu | 1 setiap satu |

## 3. Route yang PERLU sasaran baharu

⚠️ **DIBETULKAN selepas ukuran lanjut** — tiga daripada empat baris di bawah ternyata
**sudah ada set sasaran penuh**, cuma pada sub-route `/create`:

| Guide | Langkah | Sasaran sedia ada pada `<route>/create` |
|---|---|---|
| `tenant.delegasi` | 6 | `delegation-principal` `delegation-delegate` `delegation-capabilities` `delegation-starts` `delegation-ends` `delegation-reason` `delegation-submit` |
| `tenant.retensi-peraturan` | 5 | `retention-record-type` `retention-prefix` `retention-years` `retention-action` `retention-note` `retention-submit` |
| `tenant.classification-nodes` | 5 | `classnode-parent` `classnode-code` `classnode-title` `classnode-sensitivity` `classnode-level` `classnode-submit` |

**Punca salah baca saya:** skrip inventori mengira `s.route ?? g.route`, dan langkah-langkah
ini tidak mengisytiharkan route sendiri — jadi ia mewarisi route **SENARAI** sedangkan
teksnya menerangkan **BORANG CIPTA** ("Pilih Principal…", "Isi tahun simpanan…",
"Gunakan pola kod seperti 500-1/2").

➡️ **Pemetaan yang betul:** tetapkan `route` langkah kepada sub-route `/create` DAN sasarkan
medan borang. Runtime akan menavigasi ke sana sendiri (mekanisme yang sama seperti
`kind: 'navigate'` yang W4 sahkan berfungsi). Ini mengubah skop W5 secara material:
**~13 daripada 16 langkah itu memerlukan SIFAR sasaran baharu.**

Baki yang benar-benar memerlukan sasaran baharu:

| Route | Langkah | Nota |
|---|---|---|
| `/delegasi` (senarai) | 2 | "Semak nama *bagi pihak*" · "Batal delegasi" — kawalan pada BARIS |
| `/classification-nodes` (senarai) | 1 | "Cari kod/tajuk sedia ada" — medan carian jadual |
| `/retensi-peraturan` (senarai) | 1 | "Semak peraturan lalai platform" |
| **`/admin/*` (7 route)** | **21** | Panel superadmin — lihat §4 |

## 3A. ⚠️ Skrin KOSONG — diukur SEBELUM menulis kod (§5.1 dipatuhi)

```
delegasi (delegations)          0   ← kosong
retensi-peraturan               0   ← kosong (override tenant; lalai platform diwarisi)
admin/storage-orders            0   ← kosong
classification-nodes           40   ✔
admin/mosques                   2   ✔
admin/users                    11   ✔
sensitive_access_logs           1   ✔   (benih W4)
record_correction_requests      1   ✔   (benih W4)
disposal_batches                2   ✔   (benih W4)
```

Tiga skrin kosong. Nasib baik, kebanyakan langkahnya merujuk **borang cipta** (yang dirender
tanpa data), jadi hanya **4 langkah** benar-benar memerlukan baris:
`/delegasi` ×2, `/retensi-peraturan` ×1, `/admin/storage-orders` ×1 daripada 3.

⛔ **JANGAN** jalankan `RetentionRuleSeeder` — pelan melarangnya. Jika `/retensi-peraturan`
memerlukan baris, tambah SATU override demo dalam `DemoSeeder` (data sahaja, bukan enjin
retensi yang §0.3 lindungi).

## 4. 🔁 Corak `/admin/*` — satu corak menyelesaikan 21 langkah

Ketujuh-tujuh guide `admin.*` mempunyai bentuk **yang sama persis**, 3 langkah:

```
#1  "Sahkan anda berada di panel Pentadbir Platform."      → nav-primary (sasaran LOGIK, terbukti)
#2  <tindakan khusus halaman>                              → sasaran BAHARU per halaman
#3  "Semak status akhir dan jangan ulang tindakan jika …"  → konsep → justifikasi
```

Route: `/admin` · `/admin/mosques` · `/admin/users` · `/admin/storage-orders` ·
`/admin/status-sambungan` · `/admin/whatsapp-platform` · `/admin/tetapan-platform`.

Jadi daripada 21 langkah: **7 → `nav-primary`** (kos sifar), **7 → sasaran baharu**,
**7 → justifikasi**. Hanya tujuh atribut DOM baharu diperlukan untuk seluruh panel admin.

---

## 5. ⚠️ DUA corak W4 yang MESTI disemak dahulu

Kedua-duanya dibayar mahal dalam W4 (lima pusingan gate) dan akan berulang di sini.

### 5.1 Skrin KOSONG untuk peranan tertentu

**TIGA daripada lima punca W4** ialah ini. Gate melaporkannya dengan mesej yang berlainan
sepenuhnya setiap kali, jadi ia sentiasa kelihatan seperti pepijat baharu:

| Skrin | Mesej gate | Punca sebenar |
|---|---|---|
| `/pembetulan-rekod` | "sasaran dijangka: tiada" | jadual kosong |
| `/sensitive-access-logs` | "sasaran dijangka: tiada" | jadual kosong |
| `/kelulusan` (setiausaha) | "sasaran dijangka: tiada" | berskop kepada pelulus |
| `/minit-saya` (setiausaha) | **"klik maju tidak menambah tepat satu langkah"** | berskop kepada penghantar/penerima |

**Mesej gate menuding pada assertion yang gagal, bukan pada keadaan yang menyebabkannya.**

➡️ **Sebelum memetakan sebarang sasaran BARIS pada W5:** jalankan pertanyaan DB — *adakah
skrin ini ada baris untuk peranan yang guide ini tujukan?* Ini murah (satu `select`) dan
menjimatkan satu pusingan gate penuh setiap kali.

⚠️ `/admin/*` dijalankan sebagai **superadmin**, jadi skop tenant tidak terpakai — tetapi
jadual `mosques`/`users`/`storage-orders` masih boleh kosong dalam benih. Perlu disemak.

### 5.2 Proksi "langkah ini generik"

Setiap tempat dalam harness yang menyimpulkan sesuatu daripada `status === 'specific'` atau
`route === null` **pecah serentak** apabila wave menjadikan semua langkah spesifik. Empat
daripada lima punca W4 adalah varian corak ini. Semak harness untuk baki proksi sebelum mula.

---

## 6. Mekanisme sedia ada untuk diguna semula

| Mekanisme | Guna |
|---|---|
| `resources/js/help/page-target-plan.js` | pemetaan sasaran vendor PER HALAMAN (carian/tapisan jadual) |
| `tests/Feature/Help/PageTargetSelectorTest.php` | sauh kelas vendor — merah dalam 7s, bukan selepas shard 25 min |
| `resources/help/step-justifications.json` + `justified_waves` (TIGA penjaga) | allowlist justifikasi bertarikh |
| `AKSI_KOREOGRAFI` + had bawah/atas `assertTrailTargets` | julat koreografi |
| `bukti/plan-f6-w4/skrip/w4-map.mjs` | inventori — tukar `wave === 'W5'` |
| `bukti/plan-f6-w4/skrip/{tambah-registri,aktifkan,sunting-katalog}-w4.mjs` | mutasi registri/katalog satu batch |

## 7. Ramalan (ditulis SEBELUM kod)

1. Struktur kekal: `guides 83`, `steps 473`, `unique_step_ids 470`.
2. `W5` ialah baldi terminal shard `tenant-admin-public` — kiraan guide **kekal 35**, tidak
   jatuh ke 0. (Pelajaran W4: saya meramal W4→0 dan itu salah.)
3. `generic_declared` **159 → ≈20–30** (baki = W3 8 + W4 5 + W6 2 + justifikasi W5).
4. `action_generic` kekal **0**.
5. Metrik KOHORT 25/124 akhirnya bergerak — ini satu-satunya wave yang menggerakkannya.
6. Aset Vite **berubah jika** `page-target-plan.js` disentuh (jadual `/admin` mungkin perlu
   carian/tapisan). Jika hanya Blade/katalog, nama aset kekal — tetapi **ukur, jangan andaikan**
   (ramalan aset saya salah pada W4).
