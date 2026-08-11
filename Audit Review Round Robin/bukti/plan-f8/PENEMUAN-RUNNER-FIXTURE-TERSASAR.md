# F8 — runner produksi mencipta fixture pada PRODUKSI lalu mati sebelum satu ujian pun berjalan

**Tarikh:** 11 Ogos 2026, 23:13 · **run_uuid:** `2a5b03ce-f91e-4d99-994c-84b95cfa3972`
**Pencetus:** pemilik membekalkan kredensial superadmin melalui `.e2e-prod-credentials.local.json`;
larian §9.1a pertama yang benar-benar sampai ke produksi.

Ini kegagalan yang paling teruk jenisnya dalam F8: bukan gate hijau yang tidak menguji apa-apa,
tetapi **skrip yang memutasi produksi sebelum ia tahu ia boleh bekerja**, dan kemudian gagal
membersih. Ia tidak pernah terdedah sebelum ini kerana wrapper tidak pernah dijalankan sampai
titik ini — kegagalan terdahulu (`No tests found`, dibaiki pada 9 Ogos) berhenti lebih awal.

## 1. Apa yang berlaku, mengikut urutan

```
23:13:43  inventory BEFORE            → mosques 2 · users 9 · run_scoped 0
23:13:5x  prepare                     → tenant smoke-2a5b03ce… (id 7) + 8 akaun (id 38–45)  ⬅ MUTASI PRODUKSI
23:13:5x  Get-ContainerFile → cakera  → fail rahsia (8 kata laluan) ditulis ke %TEMP%
23:13:5x  icacls …/grant:r hakim:(R)  → ACE menjadi `HAKIM\:(R)`
23:13:5x  Get-Content $localSecret    → GAGAL: "Access to the path … is denied"   ⬅ PUNCA SEBENAR
          cleanup --json=…            → "Fail inventori tiada."                   ⬅ GAGAL
          Remove-Item $localSecret    → "Access to the path … is denied"          ⬅ GAGAL
```

Keputusan akhir: **produksi memegang tenant + 8 akaun tersasar**, kata laluan lapan akaun itu
**kekal dalam `%TEMP%` dan tidak boleh dipadam** *dan* **kekal dalam `/tmp` kontena produksi**,
dan **punca sebenar tidak kelihatan sama sekali** — satu-satunya ralat yang dilaporkan ialah
`Access denied` pada fail sementara, yang kelihatan seperti gangguan remeh di hujung larian.

### ⚠️ Pembetulan kepada versi pertama dokumen ini

Versi pertama saya menamakan `Process.Start('npx')` sebagai punca larian itu gagal. **Itu salah**,
dan bukti kontena membetulkannya:

```
/tmp/diwan-audit-2a5b03ce….json            3634 bait  ← fail rahsia PENUH, masih ada
/tmp/diwan-audit-2a5b03ce….inventory.json  TIADA
```

Wrapper memadam fail rahsia kontena (baris 141) dan menulis inventori teredaksi (baris 140)
**sebelum** ia melancarkan Playwright. Fail rahsia yang masih ada + inventori yang tiada
membuktikan pelaksanaan berhenti **lebih awal** daripada kedua-dua baris itu — iaitu pada
`Get-Content $localSecret` (baris 128), fail yang baru sahaja dikunci oleh `icacls`.
Kecacatan `npx` adalah **benar tetapi terpendam**: ia akan menjadi kegagalan berikutnya, dan
dibuktikan secara berasingan oleh counterexample §5 — bukan oleh larian ini.

## 2. Empat kecacatan bebas, semuanya dalam wrapper

| # | Kecacatan | Ukuran yang membuktikannya |
|---|---|---|
| 1 | **`$psi.FileName = 'npx'` dengan `UseShellExecute = $false` tidak boleh dilancarkan pada Windows** — `npx` ialah `npx.cmd`, dan `CreateProcess` tidak menyelesaikan `PATHEXT`. **Terpendam** dalam larian ini (kecacatan #2 menembak dahulu), tetapi akan menjadi kegagalan berikutnya | `Process.Start('npx')` → *cannot find the file specified*; `Process.Start('npx.cmd')` → **exit 0, versi 11.6.2**. Dua panggilan, beza satu sambungan fail. |
| 2 | ⭐ **PUNCA LARIAN ITU: ACL fail rahsia menafikan skrip itu sendiri** — `"$($env:USERNAME):(R)"` menghasilkan ACE yang bukan milik pengguna ini, dan kerana pewarisan dibuang serentak, hasilnya ialah fail yang **tidak boleh dibaca mahupun dipadam** oleh proses yang baru menciptanya | Kawalan dua hala, diukur: corak **LAMA** → ACE `HAKIM\:(R)`, `Get-Content` **GAGAL BACA (Access denied)**; corak **BAHARU** (`"$env:USERDOMAIN\$env:USERNAME:(F)"`) → **BOLEH DIBACA: True · BOLEH DIPADAM: True**. Nama mesin (`HAKIM`) dan nama pengguna (`hakim`) adalah sama pada mesin ini, yang menjelaskan mengapa `icacls` menyelesaikan nama kosong itu kepada sesuatu yang bukan pengguna. |
| 3 | **Ralat dalam `finally` MENGGANTIKAN pengecualian asal** | Punca (#1) tidak muncul dalam output; yang dilaporkan ialah kegagalan `Remove-Item`. Kecacatan #1 hanya ditemui dengan mengulang hipotesis di luar wrapper. |
| 4 | **Memadam rahsia berada SEBELUM assertion delta cleanup** dalam `finally` | Lemparan pada baris 211 melangkau semakan `run_scoped` pada baris 215+ — jadi "cleanup tidak lengkap" tidak pernah diassert pada larian yang cleanup-nya memang tidak lengkap. |

⚠️ Perhatikan gabungan #3+#4: kecacatan yang paling remeh (fail sementara tidak boleh dipadam)
**memadamkan** diagnostik untuk yang paling serius (produksi dimutasi tanpa sebab) **dan**
mematikan penjaga yang sepatutnya berteriak. Itu bukan tiga bug bebas — itu satu rantaian
yang menukar kegagalan boleh-baca menjadi kegagalan senyap.

## 3. Produksi dipulihkan (dibuktikan, bukan didakwa)

```
cleanup --force  → {"users":8,"mosques":1,"login_tokens":0}
inventory        → mosques 2 · users 9 · run_scoped: mosque_exists=false · run_users=0
```

`mosques 2 · users 9` **identik** dengan inventori BEFORE larian itu. Fail kredensial dalam
`%TEMP%`: dipadam, baki `0`.

## 4. Pembaikan — dan mengapa ia bukan sekadar "tukar `npx` ke `npx.cmd`"

Menukar nama fail sahaja akan membetulkan larian ini dan meninggalkan corak itu hidup: fixture
masih akan dicipta pada produksi sebelum runner terbukti boleh berjalan. Jadi:

1. **`npx` diselesaikan melalui `Get-Command`** (bukan diteka), dan lemparan `Process.Start`
   dibalut dengan mesej yang menamakan `PATHEXT`/`.cmd`.
2. ⭐ **PRA-TERBANG WAJIB sebelum apa-apa dicipta pada produksi** — `playwright test
   --project=production-readonly --list` dijalankan melalui **mekanisme pelancaran yang SAMA**,
   dengan 8 akaun sintetik `@invalid.test` yang tidak pernah dihantar ke mana-mana. Ia
   membuktikan: runner boleh dilancarkan · config dihurai · project wujud · spec diimport ·
   ujian dikutip. **Anti-vakum:** kiraan mesti `>= 22` (2 kontrak + 20 konteks), kerana
   `--list` yang mengutip sifar ujian boleh keluar 0.
   Pra-terbang ini menangkap KEDUA-DUA kegagalan runner yang sudah berlaku — `No tests found`
   (9 Ogos) dan `npx` (11 Ogos) — pada titik yang tidak meninggalkan kesan pada produksi.
3. **ACL:** prinsipal berkelayakan + `(F)`; pewarisan tetap dibuang, jadi akses masih terhad.
4. **`catch` luar** merekod pengecualian asal ke `run.log` sebelum `finally` berjalan.
5. **Memadam rahsia dibalut `try/catch`** (dengan cubaan kedua selepas mengeraskan ACL), supaya
   ia tidak boleh lagi melangkau assertion delta.

## 5. Counterexample MERAH (pembaikan tidak dipercayai tanpa ini)

Kecacatan #1 dipulihkan dengan sengaja (`$npxPath = 'npx'`), wrapper dijalankan dengan
`run_uuid` baharu `f08a473f-2b4d-45d0-be98-678fbd81eddf`:

```
npx = npx
run-production-guidance-readonly.ps1: … cannot find the file specified
exit = 1

Adakah apa-apa dicipta pada PRODUKSI?
    "mosques": 2,          ← tidak berubah
    "users": 9,            ← tidak berubah
    "mosque_exists": false
    "run_users": 0
```

Kecacatan yang **sama**, enam minit sebelum itu, mencipta tenant + 8 akaun. Kini ia berhenti
sebelum satu panggilan SSH pun dibuat. Pembaikan kemudian dipulihkan.

## 6. Larian kedua DIBUNUH DARI LUAR pada minit ~21 — dan mengapa itu penting

Larian yang dibaiki (`run_uuid a9d367f9…`) melepasi pra-terbang (**22 ujian dikutip**) dan
menyelesaikan **7/20 konteks · 146 halaman · 0 ralat console**. Kemudian mesin membunuh tugas
latar itu pada minit ~21 — bersama-sama suite Pest yang berjalan serentak. Kedua-duanya mati
pada saat yang **sama**, dan saya sedang menjalankan **tiga** tugas latar (dua kerja + satu
monitor). Itu penjelasan yang paling konsisten dengan bukti: had bilangan tugas latar, bukan had
masa.

Kesan: `finally` tidak pernah berjalan → fixture tersasar pada produksi **untuk kali kedua**.
Kali ini pemulihan berjalan sebagaimana direka, dan ⭐ **assertion delta benar-benar menembak**
(pembaikan #4 terbukti dalam keadaan sebenar, bukan dalam ujian):

```
cleanup selesai (idempotent): {"users":8,"mosques":1,"login_tokens":0}
delta cleanup: mosque_exists=False run_users=0
exit 0
```

`smoke` (tenant gate deploy) utuh; `mosques 2 · users 9` = identik dengan asas.

### Mod KETULAN (bukan larian tunggal)

Latihan tempatan 9 Ogos hanya menyelesaikan 20/20 selepas dipecahkan berketul; keputusan yang
sama kini terpakai pada produksi. Tiga suis ditambah pada wrapper — **satu `prepare`, beberapa
larian, satu `cleanup`**:

| Suis | Kesan |
|---|---|
| `-Grep` | dihantar sebagai `--grep` (menapis ikut TAJUK ujian) |
| `-KeepFixture` | langkau cleanup + kekalkan rahsia tempatan untuk ketulan berikutnya |
| `-UseExistingFixture` | langkau `prepare`; guna fixture run_uuid yang sama |

Ini SAH kerana keadaan per-konteks spec sudah **disandarkan pada cakera dan dikunci pada
`run_tenant`** (`bacaKeadaan()` guna semula fail apabila slug sepadan), dan kontrak penutup
membaca daripada cakera — bukan daripada kaunter dalam-memori. **Tiada perubahan spec diperlukan.**

Partition disahkan dengan `--list` SEBELUM digunakan (bukan diandaikan):

```
A desktop (public|superadmin|admin_masjid|pengerusi|setiausaha)  5
B desktop (bendahari|nazir|ajk|audit)                            4
C desktop ketua_imam                                             1   ← diasingkan (lihat bawah)
D mobile  (public|superadmin|admin_masjid|pengerusi|setiausaha)   5
E mobile  (bendahari|nazir|ajk|audit)                            4
F mobile  ketua_imam                                             1
G kontrak                                                        2
                                                     JUMLAH = 22 ✔
```

Dan corak yang tidak memadan apa-apa memberi **0 ujian, exit 1** — jadi ketulan yang tersalah
skop gagal dengan kuat dan bukan lulus secara vakum.

### 🔴 Gantung itu BUKAN rawak: kedua-duanya pada `/delegasi`

| Larian | Konteks tergantung | `cuba` terakhir yang direkod |
|---|---|---|
| `a9d367f9…` | `desktop · ketua_imam` | `/app/<tenant>/delegasi` |
| `7982012d…` | `desktop · admin_masjid` | `/app/<tenant>/delegasi` |

Halaman yang **sama** siap dalam **0.7–2.5 saat** pada enam konteks lain (superadmin 688 ms,
admin_masjid 2,535 ms, pengerusi 1,737 ms, setiausaha 1,757 ms, bendahari 1,808 ms, nazir
1,695 ms). Jadi ~2 daripada 8 lawatan ke `/delegasi` tergantung sepenuhnya.

**Apa yang berlaku pada pelayar (log nginx produksi):**

```
GET  /app/<tenant>/delegasi                                    200 114 KB
GET  /app/<tenant>/delegasi/create?panduan=tenant.delegasi&langkah=0   200 138 KB   ⬅ TIDAK diminta oleh spec
POST /livewire/update                                          200
(kemudian SIFAR permintaan sehingga had menyeluruh membunuh larian)
```

⚠️ **Pembetulan kepada bacaan pertama saya.** Saya menganggap URL `?panduan=` itu bukti halaman
"melayari dirinya sendiri" secara tidak wajar. Saya menyemaknya, dan ia **tingkah laku yang
DIREKA** — bukan kecacatan:

- `HelpLauncher::render()` menetapkan `autoStart = true` apabila pengguna tiada `GuidanceProgress`
  untuk panduan semasa dan keutamaannya membenarkan auto-mula;
- `help.js bootRuntime()` melihat `autoStart === '1'` → `startGuide(...)` selepas 450 ms;
- `help.js:883` — jika langkah PERTAMA panduan tinggal pada laluan lain, ia memanggil
  `window.location.assign('<route>?panduan=…&langkah=…')`.

Langkah pertama `tenant.delegasi` berada pada `/delegasi/create`, jadi lawatan biasa ke
`/delegasi` **memang** sepatutnya melompat ke sana. Akaun fixture ialah pengguna baharu
sepenuhnya, jadi setiap lawatan mereka mencetuskannya.

### ⭐ Punca gantung: `page.evaluate()` ialah SATU-SATUNYA tunggu yang TIDAK TERIKAT

Gantung itu dipersempit dengan **penghapusan**, bukan tekaan — setiap tunggu lain dalam laluan
itu mempunyai had, dan tiada satu pun menembak dalam 8–12 minit:

| Operasi dalam laluan `/delegasi` | Had | Menembak? |
|---|---|---|
| `page.goto(url)` | `navigationTimeout: 60_000` (config) | tidak |
| `expect(page.locator('main')).toBeVisible()` | `expect.timeout: 30_000` | tidak |
| **`page.evaluate(...)`** (ukur overflow) | **TIADA — Playwright tidak mengenakan had** | — |
| had per-ujian | `test.setTimeout(600_000)` | **tidak** (dikuatkuasakan DI DALAM worker yang terkunci) |

Kalau `goto` yang tergantung, ia akan gagal pada 60 saat. Ia tidak. Satu-satunya operasi yang
boleh menunggu 12 minit ialah `page.evaluate()` — dan ia berlumba dengan `location.assign(...)`
yang dijadualkan 450 ms selepas DOMContentLoaded oleh auto-mula tour.

**Pembaikan** (`assertHalamanSihat`): biarkan pelayaran mendap dahulu
(`waitForLoadState('domcontentloaded')`, terikat pada `navigationTimeout`), kemudian jalankan
evaluasi melalui `Promise.race` berhad 45 saat yang menamakan laluan dan sebabnya. Nilai yang
diukur **tidak berubah** — hanya tunggu tanpa had menjadi terikat. Gantung senyap bertukar
menjadi kegagalan yang boleh dibaca.

### Mengapa mesin "membunuh" larian: KESENYAPAN, bukan masa

Tiga pembunuhan, tempoh yang sangat berbeza — **21 min**, **8.5 min** — jadi ia bukan had masa,
dan bukan had bilangan tugas (yang kedua ialah satu-satunya tugas latar). Persamaan sebenar:
setiap kali, tugas itu **tidak mengeluarkan output** selama beberapa minit kerana ia tergantung.
Suite Pest yang mati serentak juga senyap — ia disalurkan melalui `tail`, jadi tiada output
berperingkat. ⚠️ Ini bermakna "dibunuh dari luar" dalam nota terdahulu ialah **gejala gantung**,
bukan had persekitaran yang bebas. Betulkan gantung → kesenyapan hilang → pembunuhan berhenti.

### `ketua_imam` diasingkan kerana ia TERGANTUNG pada produksi

Log nginx produksi memberi diagnosis yang tepat: permintaan **terakhir** pada 23:29:45, kemudian
**sifar permintaan selama 12+ minit** sementara ujian itu masih "berjalan". Jadi gantung itu di
sebelah **klien**, bukan pelayan — tandatangan yang sama seperti latihan tempatan. Permintaan
terakhir sebelum senyap:

```
GET /app/smoke-a9d367f9…/delegasi/create?panduan=tenant.delegasi&langkah=0   200
POST /livewire/update                                                        200
(kemudian tiada apa-apa)
```

Meletakkannya dalam ketulan sendiri bermakna gantungnya membakar satu ketulan, bukan lima.

## 7. Pelajaran

**Skrip yang memutasi sistem produksi mesti membuktikan ia boleh menyelesaikan kerjanya
SEBELUM mutasi pertama.** Susunan `prepare → jalankan → cleanup` menganggap langkah tengah
boleh dimulakan. Apabila anggapan itu palsu, `try/finally` tidak mencukupi — kerana laluan
cleanup itu sendiri belum pernah dijalankan dan boleh rosak juga (dan di sini, memang rosak).

Sepasang lagi, lebih halus: **jangan biarkan pembersihan rahsia mendahului assertion
integriti**. Rahsia yang tertinggal ialah masalah; produksi yang tertinggal kotor tanpa
sesiapa diberitahu ialah masalah yang lebih besar.
