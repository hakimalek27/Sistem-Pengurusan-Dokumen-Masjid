# Skrip kerja F6-W4

Enam fail ini asalnya hidup dalam **scratchpad sesi** (folder Temp Windows) dan akan hilang
apabila PC dimulakan semula. Ia dimasukkan ke repo kerana `HANDOVER.md` merujuknya secara
langsung dan kerana W5 akan menggunakan semula corak yang sama.

| Fail | Guna |
|---|---|
| `deploy-10.sh` | Deploy W4 ke bakwim.my. **Belum dijalankan** — menunggu gate hijau. Jalankan sebagai FAIL di pelayan (`scp` dahulu), JANGAN `ssh 'bash -s' <`. Mengandungi rantaian bukti 5A penuh. |
| `gate-w4.sh` | Gate tempatan 3 shard + agregator. DB segar antara shard; pelayan dilancar terus (`artisan serve` tidak menghantar `-d` kepada anak); output ke fail. |
| `w4-map.mjs` | Inventori: petakan setiap langkah generik → route diisytihar → sasaran sedia ada pada route itu. **Guna semula terus untuk W5** (tukar `wave === 'W4'`). |
| `sunting-katalog-w4.mjs` | Suntingan katalog satu batch dengan pengesahan liputan (setiap langkah generik MESTI dipeta) sebelum menulis. Round-trip `JSON.stringify(d,null,2)+'\n'`. |
| `tambah-registri-w4.mjs` | Tambah entri registri sebagai `reserved`. |
| `aktifkan-w4.mjs` | `reserved` → `active` bagi sasaran yang katalog rujuk, + tulis allowlist justifikasi. Melaporkan entri yatim. |

## Cara jalankan gate tempatan

```bash
bash "Audit Review Round Robin/bukti/plan-f6-w4/skrip/gate-w4.sh"
```

⚠️ Skrip ini menulis ke laluan scratchpad yang di-hardcode; kemas kini pemboleh ubah `OUT`
di dalamnya kepada folder sesi semasa sebelum menjalankannya.

⛔ **Jangan `TaskStop` skrip ini lalu terus menyunting sumber.** TaskStop tidak membunuh cucu:
skrip terus berjalan dan memulakan shard seterusnya dengan sumber yang sudah berubah,
menghasilkan keputusan yang tidak sah. Bunuh mengikut baris arahan
(`playwright|gate-w4|8092|guidance-full`) dan sahkan port 8092 kosong dahulu.
