# F8 nota D — `role_routes` lapisan C: mengapa `actual_status` KEKAL null adalah BETUL

**Tarikh:** 9 Ogos 2026 · **Artifak:** `role-routes-lapisan-c.json` (larian `--probe` penuh)
**Jangan** jalankan `diwan:role-routes --probe --json=…/plan-baseline/manifest.json`.

Baris §9 melaporkan lapisan C sebagai ⚠️ kerana **kesemua 410 `actual_status` = `null`** dalam
manifest beku. Bacaan yang jelas ialah "ukuran ini tertinggal — jalankan sahaja `--probe`".
Diukur, bacaan itu **salah**, dan menjalankannya akan merosakkan baseline.

## 1. Prob dijalankan — ke fail BERASINGAN, bukan ke manifest

Manifest ialah baseline beku F0; menulis ke dalamnya bermakna baseline tidak lagi mewakili F0.
Jadi `--json=` ditujukan ke `bukti/plan-f8/role-routes-lapisan-c.json`.

Keputusan:

```
37 MISMATCH antara lapisan (expected vs declared/actual)
```

## 2. Corak mismatch menghapuskan tafsiran "drift kawalan akses"

```
(expected_access, declared_access, expected_status, actual_status) → bilangan
('deny',  'deny',  302, 500) → 36
('allow', 'allow', 200, 500) →  1
```

⭐ **Keputusan AKSES bersetuju pada kesemua 37** (`deny`=`deny`, `allow`=`allow`). Yang berbeza
hanyalah **status**. Jadi tiada identiti mendapat capaian yang ia tidak sepatutnya ada — isunya
ialah apa yang prob itu LAPORKAN, bukan apa yang sistem BENARKAN.

## 3. HTTP sebenar pada laluan yang SAMA memberi status yang dijangka

Tiga daripada 36 diuji terus terhadap pelayan hidup, tanpa sesi (iaitu tetamu — keadaan yang
sama seperti identiti `public`):

```
/app/mam/analitik-bantuan  -> HTTP 302     (manifest jangka 302)  ✔
/app/mam/bantuan           -> HTTP 302     (manifest jangka 302)  ✔
/app/mam/carian            -> HTTP 302     (manifest jangka 302)  ✔
```

**500 itu ialah artifak prob, bukan tingkah laku aplikasi.** Prob `--probe` memanggil kernel
HTTP secara dalaman; ia tidak menjalankan tindanan middleware/sesi penuh yang menghasilkan
pengalihan Filament ke halaman log masuk. Arahan itu sendiri sudah melabelkannya:
*"probe HTTP dalaman lapisan C (**best-effort**; C autoritatif = PlanManifestTest + runner F8)"*.

Mismatch ke-37 (`superadmin` → `/admin`, jangka 200) ialah keluarga yang sama: ia memerlukan
sesi berautentikasi, yang prob dalaman tidak bina.

## 4. Maka: mengisi manifest dengan `--probe` akan MEROSAKKAN baseline

Jika `actual_status` diisi daripada larian ini, manifest beku akan mengandungi **37 mismatch
palsu** — dan penjaga yang membaca `mismatches` sebagai syarat keluaran akan merah selamanya
atas sebab yang tidak wujud. `null` bermaksud "tidak diukur oleh alat ini", dan itu **jujur**.

## 5. Lapisan C SUDAH dikuatkuasakan — oleh mekanisme yang betul

`PlanManifestTest` menjalankan probe HTTP **sebenar** per identiti pada SETIAP larian suite
(bukan sekali semasa manifest dijana), melalui klien ujian Laravel yang memang membina sesi dan
middleware penuh:

```
role_routes lapisan C: probe HTTP sebenar sepadan expected_status with ('ajk')    ✓
role_routes lapisan C: probe HTTP sebenar sepadan expected_status with ('audit')  ✓
…  (16 ujian, 3,167 assertion, hijau)
```

Runner produksi §9.1a menyediakan lapisan ketiga pada sistem hidup.

## 6. Cadangan

Tukar baris §9 daripada ⚠️ "TIDAK dalam manifest" kepada **✅ dengan syarat**: lapisan C
dikuatkuasakan oleh `PlanManifestTest` pada setiap larian; medan `actual_status` dalam manifest
kekal `null` **dengan sengaja** kerana penjananya best-effort, dan artifak ini merekod sebabnya
supaya orang berikutnya tidak "membaikinya".

⚠️ Yang saya TIDAK dakwa: bahawa ke-36 laluan itu memberi 302 pada PRODUKSI. Yang diuji ialah
pelayan tempatan. Runner §9.1a akan mengesahkannya pada produksi.
