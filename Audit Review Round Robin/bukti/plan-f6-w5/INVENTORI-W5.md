# INVENTORI F6-W5 — shard `tenant-admin-public`

Ditulis **sebelum** sebarang kod W5, seperti W2/W3/W4. Semua angka DIUKUR daripada
`manifest.json` + `guides.json` + `targets.json` + **render sebenar pada data demo**,
bukan daripada jadual beku pelan.

Asas: `local = origin = server = cea55da` (Deploy 10, F6-W4 LIVE).

> **REVISI 2 (7 Ogos 2026).** Versi pertama dokumen ini mengandungi **dua kesilapan saya
> sendiri** yang kini dibetulkan di tempatnya. Kedua-duanya direkod di §0 supaya corak
> kesilapan itu tidak berulang pada W6.

---

## 0. Dua pembetulan terhadap versi pertama dokumen ini

### 0.1 Saya tersilap baca skrip inventori saya sendiri (dibetulkan pada Revisi 1)

Tiga route dilaporkan "tiada sasaran" sedangkan set penuh sudah wujud pada sub-route
`/create`. Puncanya: skrip mengira `s.route ?? g.route`, dan langkah-langkah itu tidak
mengisytiharkan route sendiri — jadi ia mewarisi route **SENARAI** sedangkan teksnya
menerangkan **BORANG CIPTA**.

### 0.2 Inventori UNDERCOUNT route yang perlukan sasaran baharu (dibetulkan pada Revisi 2)

Versi pertama menyenaraikan **7** route `/admin/*` (21 langkah). Ukuran semula:

```
/admin/* 3-langkah   : 8 route (bukan 7) = 24 langkah   ← /admin/profil-saya terlepas
/admin/* 2-langkah   : 4 route            =  8 langkah   ← TIDAK disebut langsung
/app/{tenant} 2-lgkh : 4 route            =  8 langkah   ← TIDAK disebut langsung
```

**16 langkah tidak pernah muncul dalam jadual asal.** Puncanya: jadual §2 versi pertama
disenaraikan secara manual daripada ingatan tentang route "utama", bukan dijana daripada
output skrip. Output skrip sentiasa betul; ringkasan manusia yang menyimpang.

➡️ **Peraturan untuk W6:** jadual dalam dokumen inventori mesti **dijana** daripada skrip,
bukan ditaip semula.

---

## 1. Skop (diukur)

```
guide W5   : 35        langkah: 146        generik: 144
family     : tenant 112 · admin 32
wait_for_user : false 144/144   (KESEMUANYA langkah PENERANGAN)
viewport      : desktop 144/144

taburan generik : 88 pada route yang SUDAH ada sasaran · 56 pada route tanpa sasaran
kohort          : 23 daripada 35 guide W5 berada dalam KOHORT 25 (semua `tenant.*`)
```

W5 ialah **wave TERBESAR yang tinggal** dan satu-satunya yang menggerakkan metrik
**KOHORT `resolved_to_generic` 119/124**.

---

## 2. ⛔ KEKANGAN REKA BENTUK YANG MENENTUKAN SEGALANYA

**Kesemua 144 langkah `wait_for_user: false`.** Akibatnya, dalam `stepAdvancePlan` setiap
satu mendapat kind `advance` — CTA berlabel "Seterusnya" yang **memajukan tour**, bukan
"Buat pada skrin" yang menunggu tindakan.

Kesan pada gate (`driveFlowGuide` baris 828): cabang yang benar-benar **melakukan tindakan**
pada elemen disorot hanya berjalan apabila `label === 'Buat pada skrin'`. Guide W5 tidak
pernah mencapai cabang itu.

➡️ **Peraturan W5-1 (mutlak): sasaran hanya boleh dinaikkan jika ia kelihatan dalam keadaan
LALAI halaman — tanpa sebarang tindakan pengguna.**

Ini mengecualikan, secara reka bentuk dan bukan kemalasan:

| Jenis `state` registri | Sebab dikecualikan |
|---|---|
| `modal:…` | modal hanya terbuka selepas klik; tiada langkah tindakan untuk membukanya |
| `wizard langkah N` | wizard onboarding ialah **modal berlangkah** (`OnboardingWizard.php:56-63`) |
| `detail:…` | URL butiran dinamik (`/records/{id}`) — katalog tidak boleh mengisytiharkannya |

Langkah yang teksnya benar-benar merujuk elemen sedemikian → `generic-justified` dengan
sebab yang DIUKUR. Pelan §7.2 membenarkan ini secara eksplisit sebagai "keputusan sedar
yang direkod".

---

## 3. §5.1 — SKRIN KOSONG, diukur pada benih demo SEBENAR sebagai `admin_masjid`

Ini semakan yang W4 bayar mahal (TIGA daripada lima punca). Dijalankan **sebelum** kod.
Kaedah: log masuk Chrome sebagai `admin_masjid@demo.test` pada pelayan tempatan, kemudian
`fetch()` setiap route dalam sesi yang sama dan kira `table tbody tr` + `[data-help-target]`.

```
route                     baris  carian  tapisan  sasaran yang BENAR-BENAR dirender
/peti-masuk                 1      Y       n      inbox-classify inbox-record inbox-scan-status inbox-upload
/registry-files             4      Y       n      regfiles-medium regfiles-view
/minit-saya                 1      n       Y      minit-record minit-status
/kelulusan                  0      n       n      (TIADA)                      ← ⚠️
/log-aktiviti               5      Y       Y      log-detail
/classification-nodes      10      Y       n      (TIADA)
/retensi-peraturan          0      n       n      (TIADA)
/delegasi                   0      n       n      (TIADA)                      ← ⚠️
/records                    3      Y       Y      records-view
/pembetulan-rekod           1      n       n      correction-decision correction-diff correction-status
/sensitive-access-logs      1      Y       n      sensitive-log-record
/penggunaan                 0      n       n      storage-add storage-orders storage-usage
/retensi                    0      n       n      retention-export retention-hold retention-schedule
/laporan                    0      n       n      report-export report-summary
/tiket-sokongan             0      Y       n      (TIADA)
/analitik-bantuan           0      n       n      page-content sahaja
/bantuan                    0      n       n      help-center help-search help-diagnosis help-preferences help-support
/app/mam (dashboard)        —      —       —      what-next  ·  .fi-wi-stats-overview ADA  ·  widget senarai semak ADA
```

### 3.1 ⚠️ DUA perangkap "skrin kosong per-peranan" ditemui SEBELUM gate

**(a) `/kelulusan` = 0 baris untuk `admin_masjid`.** `ApprovalResource` menapis
`approver_id = saya`; benih demo tidak pernah menjadikan admin masjid seorang pelulus.
Ini **corak yang sama persis** dengan penemuan W4 untuk setiausaha — dan `accountFor()`
memilih `admin_masjid` untuk guide ini kerana rolesnya mengandunginya.

**(b) `/minit-saya` = 1 baris TETAPI 0 aksi baris.** `minit-record` dan `minit-status`
dirender, tetapi `minit-complete` dan `minit-reply` TIDAK — admin masjid ialah **penghantar**
minit demo, bukan penerima tindakan, jadi butang "Tanda Selesai" dan "Balas & Edarkan"
tidak wujud untuk dia.

Kedua-duanya akan memberi kegagalan gate dengan mesej yang **berlainan sepenuhnya**, tepat
seperti W4. Jimat: sekurang-kurangnya satu pusingan gate 25 minit setiap satu.

### 3.2 Keputusan: perluas benih demo (DATA sahaja)

Tiga baris ditambah, semuanya idempoten, semuanya jenis data yang demo memang patut ada:

1. **Satu delegasi AKTIF** → `/delegasi` mendapat baris + aksi "Batal".
2. **Satu kelulusan `menunggu` dengan `approver_id = admin_masjid`** → `/kelulusan`
   mendapat baris + aksi Lulus/Tolak.
3. **Satu minit dengan `admin_masjid` sebagai penerima TINDAKAN** → `/minit-saya`
   mendapat "Tanda Selesai" + "Balas & Edarkan".

⛔ **`RetentionRuleSeeder` TIDAK disentuh** (larangan pelan). `/retensi-peraturan` kekal
kosong — pemetaannya tidak memerlukan baris (lihat §5).

⚠️ **W3:** perubahan benih pernah memecahkan `MinitService` kerana sensitiviti rekod.
Suite PENUH mesti dijalankan, bukan ujian berkaitan sahaja.

---

## 4. Sasaran sedia ada yang TIDAK PERNAH didaftarkan (penemuan baharu)

Enam sasaran dirender oleh produk tetapi **tiada langsung dalam `targets.json`**:

```
help-center  help-search  help-diagnosis  help-preferences  help-support   (livewire/help-center.blade.php)
help-launcher                                                              (livewire/help-launcher.blade.php)
```

Ujian yatim dua hala hanya membandingkan **katalog ↔ registri**; ia tidak dapat melihat
`data-help-target` dalam DOM yang tidak dirujuk mana-mana. Jadi keenam-enamnya halimunan
kepada gate sejak binaan asal.

➡️ W5 mendaftarkan lima yang pertama dan MENGGUNAKANNYA (`tenant.bantuan#1`,
`admin.bantuan#1`). `help-launcher` kekal tidak berdaftar: ia infrastruktur pelancar,
bukan sasaran tour, dan menjadikannya `active` akan menjadikannya yatim registri.

---

## 5. Pemetaan muktamad — 144 langkah

Legenda: **↑** dinaikkan kepada sasaran spesifik · **≡** dijustifikasikan (sebab diukur)
· `→/create` bermakna `route` langkah ditetapkan kepada sub-route borang.

### 5.1 Tenant — 106 langkah

| Guide | ↑ | ≡ | Nota pemetaan |
|---|---|---|---|
| `dashboard` | 2 | 0 | `dashboard-stats` (BARU) · `dashboard-checklist` (BARU) |
| `sensitive-access-logs` | 3 | 1 | carian (BARU) · `sensitive-log-record` · `sensitive-log-target` (BARU) |
| `log-aktiviti` | 4 | 1 | `log-filters` `log-search` `log-detail` · `log-time` (BARU) |
| `persediaan` | 2 | 4 | `onboarding-start` · `nav-primary`; 4 langkah wizard = modal |
| `ahli-peranan` | 3 | 3 | `members-invite` + 2 BARU; medan jemputan dalam modal |
| `classification-nodes` | 4 | 1 | carian (BARU) + 3 medan →/create |
| `retensi-peraturan` | 4 | 1 | #1 →`/retensi` `retention-schedule` + 3 medan →/create |
| `tetapan-masjid` | 2 | 4 | ringkasan + WhatsApp (BARU); medan tetapan dalam modal |
| `penggunaan` | 3 | 2 | `storage-usage` `storage-orders` `storage-add` |
| `retensi` | 3 | 2 | `retention-hold` `retention-schedule` `retention-export` |
| `delegasi` | 5 | 1 | 4 medan →/create · `delegation-revoke` (BARU + benih) |
| `profil` | 4 | 2 | `profil-akaun` `-notifikasi` `-ujian` `-kata-laluan` |
| `peti-masuk` | 6 | 0 | 4 sedia ada + 2 BARU (Lihat, Sumber) |
| `records` | 2 | 3 | `records-search` `records-view`; tab & tindakan = halaman butiran |
| `registry-files` | 3 | 3 | `regfiles-search` `-medium` + 1 BARU; selebihnya butiran |
| `minit-saya` | 4 | 2 | `minit-filters` `minit-record` `minit-complete` `minit-reply` (+benih) |
| `kelulusan` | 3 | 3 | `approval-record` `-lulus` `-status` (+benih) |
| `carian` | 7 | 0 | 5 sasaran BARU pada borang carian + `search-filters` + `search-favourite` |
| `laporan` | 3 | 1 | `report-summary` · pecahan (BARU) · `report-export` |
| `pembetulan-rekod` | 2 | 3 | `correction-decision` `correction-status` |
| `bantuan` | 2 | 0 | `help-search` (didaftar §4) · `nav-primary` |
| `analitik-bantuan` | 2 | 0 | `analytics-metrics` (BARU) · `nav-primary` |
| `tiket-sokongan` | 2 | 0 | `tickets-search` (BARU) · `nav-primary` |

### 5.2 Admin — 38 langkah

Corak tetap, disahkan boleh dijalankan: **kesemua 12 route panel admin merender
`.fi-sidebar` + `.fi-topbar` + `.fi-topbar-open-sidebar-btn`**, jadi sasaran LOGIK
`nav-primary` selamat di sana — walaupun ia tidak pernah digunakan pada panel admin
sebelum ini (0 daripada 6 penggunaan sedia ada).

```
8 guide × 3 langkah : #1 nav-primary ↑ · #2 sasaran BARU per halaman ↑ · #3 konsep ≡
4 guide × 2 langkah : #1 sasaran halaman ↑ · #2 nav-primary ↑
```

→ admin: **28 ↑ · 8 ≡**

### 5.3 Jumlah dijangka

```
↑ dinaikkan        ≈ 105        generic_declared 159 → ≈ 54
≡ dijustifikasikan ≈  39        action_generic   kekal 0
```

---

## 6. Sasaran BARU yang perlu ditulis (≈21)

| Sasaran | Route | Cangkuk |
|---|---|---|
| `dashboard-stats` | `/app/{tenant}` + `/admin` | `page-target-plan.js` → `.fi-wi-stats-overview` |
| `dashboard-checklist` | `/app/{tenant}` | blade widget senarai semak |
| `search-text` `search-parties` `search-submit` `search-save` `search-saved` | `/carian` | `cari-rekod.blade.php` |
| `classnode-search` `sensitive-log-search` `tickets-search` | 3 route | `page-target-plan.js` → `.fi-ta-search-field` |
| `sensitive-log-target` `log-time` | 2 route | `extraCellAttributes` corak `baris1()` |
| `delegation-revoke` | `/delegasi` | `DelegationsTable` aksi baris |
| `inbox-view` `inbox-source` | `/peti-masuk` | `InboxTable` |
| `analytics-metrics` | 2 route (view dikongsi) | `help-analytics.blade.php` |
| 8 × sasaran halaman `/admin/*` | panel admin | jadual/blade per halaman |

⚠️ Setiap selektor `page-target-plan.js` MESTI mendapat sauh dalam
`PageTargetSelectorTest` — penjaga hanyut itu wujud kerana W4 pusingan 1 kehilangan
satu pusingan penuh atas kelas vendor yang salah.

---

## 7. Mekanisme sedia ada untuk diguna semula

| Mekanisme | Guna |
|---|---|
| `resources/js/help/page-target-plan.js` | pemetaan sasaran vendor PER HALAMAN |
| `tests/Feature/Help/PageTargetSelectorTest.php` | sauh kelas vendor — merah dalam 7s |
| `resources/help/step-justifications.json` + `justified_waves` (TIGA penjaga) | allowlist justifikasi |
| `bukti/plan-f6-w4/skrip/{tambah-registri,aktifkan,sunting-katalog}-w4.mjs` | mutasi satu batch |

## 8. Ramalan (ditulis SEBELUM kod)

1. Struktur kekal: `guides 83`, `steps 473`, `unique_step_ids 470`.
2. `W5` ialah baldi terminal shard `tenant-admin-public` — kiraan guide **kekal 35**.
3. `generic_declared` **159 → ≈54**.
4. `action_generic` kekal **0**.
5. Metrik KOHORT bergerak buat kali pertama.
6. Aset Vite **BERUBAH** — `page-target-plan.js` disentuh, jadi `help-*.js` mendapat hash
   baharu; CSS `help-CrH0eDM1.css` kekal. Deploy 11 mesti rebuild `app` DAN `nginx`.
7. Benih demo berubah → **jangan** jalankan seeder pada produksi.
