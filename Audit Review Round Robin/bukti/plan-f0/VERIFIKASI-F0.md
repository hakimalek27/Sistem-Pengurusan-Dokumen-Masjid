# Bukti Verifikasi Fasa F0 — 2 Ogos 2026

Output SEBENAR arahan verifikasi (CLAUDE.md peraturan #7: dakwaan tanpa output = tidak siap).
Asas kod: `2489492` (kod aplikasi ≡ `8342d95`).

## 1. Suite Pest penuh (termasuk ujian F0 baharu)

```
Tests:    1 skipped, 432 passed (4738 assertions)
Duration: 120.01s
```

(Baseline pra-F0 = 409 lulus / 1 skip. +23 ujian baharu: PlanManifestTest 14 —
termasuk probe lapisan C 410 entri × 10 identiti + silang-tenant 8×404 —
AuditFixtureCommandTest 5, InboxAntivirusFailClosedTest 4.)

## 2. diwan:role-routes (lapisan A+B) — 0 mismatch

```
role_routes ditulis: …/role-routes.json (410 entri).
| public       | 0  |   | superadmin   | 25 |   | admin_masjid | 25 |
| pengerusi    | 17 |   | setiausaha   | 15 |   | bendahari    | 15 |
| nazir        | 13 |   | ketua_imam   | 13 |   | ajk          | 13 |
| audit        | 14 |
Tiada mismatch expected↔declared (lapisan C dikuatkuasakan oleh PlanManifestTest).
```

Kiraan nav = 25/17/15/15/13/13/13/14 — SAMA dengan jangkaan `e2e/guidance.spec.js` lama,
kini dijana daripada spec (roles.php + polisi), menamatkan drift P14-03.

## 3. Penjana manifest + validator bebas

```
OK: manifest ditulis ke Audit Review Round Robin/bukti/plan-baseline/manifest.json
  guides=83 steps=473 actionGeneric=200 placeholder=258
  waves=W0:2g/10s W1:28g/140s W2:13g/145s W3:1g/11s W4:1g/13s W5:35g/146s W6:3g/8s
  role_routes entries=410 counts={"public":0,"superadmin":25,"admin_masjid":25,"pengerusi":17,"setiausaha":15,"bendahari":15,"nazir":13,"ketua_imam":13,"ajk":13,"audit":14}

OK: manifest sah — partition wave/shard sepadan pengiraan bebas, set-union exact, role_routes konsisten.
```

Pengesahan angka beku kali KE-5 (bebas): 83 · 473 · 443 (238+205) · 258 · 229 · 200 ·
step.id unik 470/473 · defect mobile 6 (`tenant.pelupusan#1`, `tenant.kegemaran#1–5`).

## 4. Canary sesi + ci-domain (server tempatan 8092, `serve --no-reload`, SQLite buangan)

```
[ci-guidance] › ci-session-canary.spec.js › @session-canary … 1 passed (5.9s)
OK [storage/app/plan-ci/ci-canary.json]: 1 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.

[ci-domain] ddms-extended ×2 + office-workflow ×2 … 4 passed (1.2m)
OK [storage/app/plan-ci/ci-domain.json]: 4 ujian, 0 skipped/…
```

## 5. Sampel guidance-full (sebelum larian shard penuh)

```
gate screen: screen.klasifikasi-peti-masuk (11 langkah)     1 passed (15.6s)
gate tenant-admin-public: public.login (2 langkah)          1 passed (6.5s)   ← laluan fallback risk-accepted
gate tenant-admin-public: public.registration (4 langkah)   1 passed (6.0s)   ← koreografi hantar penuh
gate tenant-admin-public: tenant.dashboard (4 langkah)      1 passed (17.5s)
gate tenant-admin-public: admin.mosques (3 langkah)         1 passed (13.9s)
```

## 6. OCR fixture — tesseract lokal membaca istilah

```
$ tesseract tests/fixtures/ocr/sample-scan-1.png stdout -l msa+eng | grep BAKTIMURNI
BAKTIMURNI
BAKTIMURNI
$ tesseract tests/fixtures/ocr/sample-scan-2.png stdout -l msa+eng | grep CAHAYAIKHLAS
CAHAYAIKHLAS
CAHAYAIKHLAS
```

## 7. Baseline runtime produksi (F0(v)) — read-only SSH

Lihat `../plan-baseline/runtime-baseline-2026-08-02.json`:
`3a=2a ✓ · 3b=2b ✓ · 4a=4b ✓ · 5a=5b=6 ✓` — server `8342d95`, aset `help-pJkQNpPs.js`
hash sama dlm app/nginx/URL awam.

## 8. ⚠️ Penemuan F0: CI main sudah MERAH sejak `4e07a70` (pra-F0)

```
$ gh run list --branch main --limit 3 …
2489492 failure   8342d95 failure   4e07a70 failure
$ gh run view <run 8342d95> … → "PostgreSQL, Redis, Meili, OCR and tests: failure |
  langkah gagal: Validate, audit and format"
```

Punca: komit dokumen audit membawa 4 skrip PHP bukti (`bukti/pusingan-04/05/06/*.php`)
yang gagal `pint --test`. Pembetulan F0: **`pint.json`** (preset laravel + exclude
`Audit Review Round Robin`) — folder bukti ialah ARKIB, bukan kod sumber; skrip bukti
kekal verbatim (tidak diformat semula). Selepas fix:

```
$ vendor/bin/pint --test
{"tool":"pint","result":"passed"}
```

## 9. composer validate --strict

```
./composer.json is valid
```

## 10. Larian penuh 3 shard guidance-full + agregator — GATE LULUS

```
=== SHARD screen ===              30 passed (8.9m)
OK [storage/app/plan-ci/guidance-full-screen.json]: 30 ujian, 0 skipped/timedOut/interrupted/unexpected/flaky.
=== SHARD workflow ===            15 passed (7.3m)
OK [storage/app/plan-ci/guidance-full-workflow.json]: 15 ujian, 0 skipped/…
=== SHARD tenant-admin-public === 41 passed (10.5m)
OK [storage/app/plan-ci/guidance-full-tenant-admin-public.json]: 41 ujian, 0 skipped/…
=== AGREGATOR ===
GATE LULUS: 83 guide · 473 langkah · 229 langkah tindakan — union tiga shard sepadan
manifest (set, bukan kiraan). Laporan: storage/app/plan-f6/coverage-gate.json
```

**3 pepijat spec ditemui & dibaiki semasa larian penuh (bukti nilai gate!):**
1. Guide `workflow.*` sebenarnya 20/13 langkah (ada ekor generik di minit-saya/log-aktiviti)
   dan jangkaan nombor tegar tidak tahan auto-advance sync → mesin-keadaan toleran
   (baca langkah semasa → tindakan → poll maju) + pemandu per-langkah utk ekor.
2. Popover tour memintas klik `Hantar` modal muat naik (**pengesahan bebas RR-08-03
   pada desktop** — bukan mobile sahaja) → minimize (CTA) sebelum setiap interaksi modal.
3. Registrasi: kod akronim mesti 3–6 HURUF + telefon WA mesti unik (penolakan pendua
   senyap dari langkah 3) → kod huruf-dari-timestamp + telefon unik per larian.

