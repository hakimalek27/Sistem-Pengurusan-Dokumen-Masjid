# Audit round-robin F8 — PUSINGAN 2 (Codex), 19 penemuan

**Tarikh:** 9 Ogos 2026 · **Pengaudit:** `codex-cli 0.144.1` · **Prompt:** `skrip/rr-f8-p2-prompt.txt`

Soalan pusingan 2: adakah pembaikan pusingan 1 BENAR-BENAR menutup penemuan, atau hanya
kelihatan menutupnya? Codex menjalankan counterexample sebenar terhadap ujian yang dikukuhkan.

⭐ **#11 mengesahkan secara BEBAS** penemuan runner yang saya temui serentak: spec produksi
tiada dalam mana-mana project Playwright, jadi arahan wrapper memberi `No tests found`.
Dua pengaudit, dua laluan, satu kesimpulan.

Triase penuh + status TERBUKA: `../../SUSULAN-PEMBAIKAN.md` §5C.

---

1. **[TERUK]** [ukur-runtime-kohort-f8.mjs:52], [runtime-kohort-f8.json:1] — artifak 124 langkah tidak terikat kepada satu larian. Skrip menyambung mana-mana hasil lama berdasarkan `key` sahaja, tanpa hash katalog, commit, tenant, benih atau masa. Baris 124 pula menulis `lengkap:true` walaupun `AB_HAD` menghentikan larian separa. Fail JSON hanya menyimpan `lengkap/definisi/asas/kini/hasil`; larian berbilang versi boleh dicampur dan tetap kelihatan lengkap.

2. **[TERUK]** [ukur-runtime-kohort-f8.mjs:25], [step-advance-plan.js:54], [production-desktop-all-tour-steps.json:3] — CTA `0` bukan apple-to-apple. Asas menggunakan produksi tenant `smoke`; ukuran baharu menggunakan pelayan tempatan, tenant `mam`, akaun demo dan benih yang tidak direkod. CTA berubah apabila sasaran seterusnya wujud atau hilang dalam DOM, jadi perbezaan benih boleh menukar `Seterusnya` kepada `Buat pada skrin` tanpa perubahan katalog.

3. **[SEDANG]** [ukur-runtime-kohort-f8.mjs:81], [help.js:708] — metrik `title==description` kini membandingkan tajuk dengan keseluruhan `textContent`, termasuk hint dan pautan “Buka panduan penuh”. Kesemua 124 deskripsi semasa mengandungi boilerplate itu, sedangkan artifak asas hanya mengandungi arahan. Metrik boleh kekal `0` walaupun tajuk kembali menduplikasi arahan teras.

4. **[TERUK]** [metrik-f8.mjs:154], [metrik-f8.json:161], [SUSULAN-PEMBAIKAN.md:310] — penemuan #6 masih terbuka. “Pecahan silang” ialah pengiraan statik medan `s.viewport`, bukan keputusan ukuran bagi setiap viewport. Kesemua 26 sel mempunyai `mobile=0`, kecuali kategori `both`; ia juga tidak memecahkan setiap metrik seperti diwajibkan §9.3. Status ✅ salah.

5. **[TERUK]** [HelpSearchGateTest.php:79] — ujian (b) masih boleh melepaskan data tenant. Set medan hanya diperiksa pada dokumen pertama. Counterexample yang dijalankan: tambah `mosque_id=123` dan `user_id=456` pada dokumen kedua; semakan kunci pertama, e-mel dan URL semuanya lulus. Penemuan #10 belum ditutup sepenuhnya.

6. **[TERUK]** [HelpSearchGateTest.php:160] — komen mendakwa objek penuh dibandingkan, tetapi kod hanya membandingkan `pluck('id')`, kemudian mencari dua slug dan e-mel dalam JSON. Perbezaan tenant dalam `title`, `summary`, metadata atau route tanpa slug lawan tetap hijau; kontekstualisasi positif hanya diassert bagi tenant A. Penemuan #12 masih terbuka.

7. **[SEDANG]** [HelpSearchGateTest.php:54], [HelpSearchGateTest.php:131], [HelpSearchGateTest.php:206] — relevansi masih diuji melalui proksi. Ujian (a) menerima mana-mana ID yang mengandungi tiga substring; (e) hanya menuntut hasil tidak kosong; (c) hanya menuntut satu hasil terkumpul daripada empat query. Guide rawak dengan ID yang “sesuai”, atau tiga query awam yang rosak, masih hijau.

8. **[TERUK]** [HelpSearchGateTest.php:263], [SyncHelpIndex.php:67] — ujian (f) masih bukan gate Meili, dan `toBeGreaterThan(0)` terlalu longgar. Buang `steps_text` daripada `updateSearchableAttributes`: semua pengiraan set, contoh fallback dan ujian (b) kekal hijau, tetapi Meili tidak lagi mencari teks arahan. Satu token yatim juga cukup untuk mengekalkan kedua-dua assertion `>0` walaupun jurang 17/38 berubah hampir sepenuhnya.

9. **[TERUK]** [RoleAccessDocTest.php:141], [RoleAccessDocTest.php:162] — gate route masih boleh lulus secara vakum per identiti/panel. Ia hanya mengaudit seksyen yang berjaya dihuraikan dan tidak menuntut setiap identiti/panel hadir. Counterexample dalam memori membuang seluruh seksyen Juruaudit sambil mengekalkan baris ringkasan: parser kekal tidak kosong dan langsung tidak mengaudit Juruaudit. Perbandingan bait tidak membantu jika penjana dan dokumen sama-sama rosak.

10. **[TERUK]** [generate-role-access-doc.mjs:105] — pembaca sejarah gagal-terbuka. Fail tiada menghasilkan “perbandingan dilangkau” dengan exit 0. Fail rosak atau heading tidak sepadan menghasilkan objek kosong yang dianggap sah; semua senarai lama menjadi kosong, `jumlah HILANG=0`, lalu penjana mengisytiharkan “KONSISTEN”. Tiada validasi lapan role, kiraan heading atau sekurang-kurangnya satu route per role.

11. **[TERUK]** `HEAD:playwright.config.js:34-84` + `HEAD:scripts/audit/run-production-guidance-readonly.ps1:87` — pada commit yang diaudit, spec produksi sengaja tiada dalam mana-mana project tetapi wrapper memanggilnya sebagai fail. Semantik itu disahkan dengan spec produksi lain: Playwright melaporkan `Total: 0 tests` dan `Error: No tests found`. Jadi status “disekat kredensial” salah; runner terlebih dahulu disekat kod. Worktree kini mempunyai pembaikan belum dikomit untuk isu ini.

12. **[TERUK]** [run-production-guidance-readonly.ps1:49], [AuditFixture.php:123] — fail rahsia dicipta di `/tmp` dalam kontena `app`, tetapi wrapper membaca dan memadam `/tmp/...` pada host SSH. `cat` tidak menemui fail kontena dan kredensial boleh kekal di dalam kontena. Ini mematahkan runner serta kontrak pemadaman rahsia.

13. **[TERUK]** [run-production-guidance-readonly.ps1:107], [AuditFixture.php:137] — cleanup wrapper tidak menggunakan inventori ID. Ia memanggil `cleanup --force` tanpa `--json`, menyebabkan command mencari pengguna melalui corak e-mel dan tenant melalui slug. Ini bercanggah dengan kontrak “padam hanya ID `created`”; wrapper juga menulis `before/after` tetapi tidak mengassert deltanya sifar.

14. **[TERUK]** [production-guidance-readonly.spec.js:92], [production-guidance-readonly.spec.js:118], [production-guidance-readonly.spec.js:164], [help-center.blade.php:65] — beberapa item §9.1 berstatus ⏸ sebenarnya belum berada dalam spec. Public tiada tour/carian/`<main>`/overflow; superadmin hanya melawat panel `admin` dan meninggalkan halaman `app`; setiap route tidak menguji bantuan/carian/tour. Tiga query pula hanya mengassert elemen status yang memang sentiasa kelihatan, tanpa hasil atau tapisan role. Ini kecacatan tempatan yang boleh dibaiki tanpa kredensial.

15. **[SEDANG]** [HelpSearchGateTest.php:43], [SUSULAN-PEMBAIKAN.md:299] — status timeout ✅ salah. Port localhost 1 memberi `connection refused`, bukan timeout. Laluan fallback selepas sambungan tergantung, had masa UX dan pembatalan request belum diuji; ini boleh disimulasi tempatan tanpa kredensial.

16. **[SEDANG]** [SUSULAN-PEMBAIKAN.md:50], [LocalisationTest.php:221], [SUSULAN-PEMBAIKAN.md:243] — penemuan #17 belum ditutup sepenuhnya. “E-mel 0/18” hanya memanggil `toMail()` dan `render()`, bukan penghantaran sebenar yang §9 tuntut. Bukti axe mempunyai JSON tetapi tiada skrinsyot 5×2. Ringkasan “24 tercapai” turut mengira baris 172-lawan-229 yang sendiri dilabel LENCONGAN sebagai tercapai.

17. **[TERUK]** [ab-mobile.sh:10], [ab-ukur.mjs:69], [ab-lama.json:2] — penemuan #22 hanya ditutup secara permukaan. Wrapper menggunakan `set -uo pipefail`, bukan `-e`; kegagalan larian A tidak menghentikan larian B atau memadam artifak lama. Ralat setiap langkah ditelan. JSON hanya merekod label dan keputusan—tiada catalog version/hash, commit, tenant, benih atau masa—jadi fail `1/24` tidak membuktikan ia benar-benar sisi katalog lama.

18. **[TERUK]** [metrik-f8.mjs:182], [manifest.json:9790], [RoleRoutes.php:230] — angka “0 mismatch pada 410 pasangan expected/declared/actual” tidak datang daripada tiga lapisan. Kesemua 410 `actual_status` dalam manifest ialah `null`; pembina `mismatches` sengaja mengabaikan actual yang null, dan `metrik-f8.mjs` hanya menyalin panjang array itu. Suite tempatan memang menjalankan probe, tetapi tiada artifak per-pasangan yang dikomit.

19. **[SEDANG]** [ci-coverage-gate-31213031582.json:8], [PENEMUAN-CARIAN.md:11], [produksi-smoke-meili.txt:13] — masih tidak boleh dihasilkan semula daripada artifak komited:

   - Union `83/473/172` hanya ada sebagai ringkasan; tiga JSON shard mentah yang dinamakan tidak dikomit.
   - Kebocoran produksi `e-mel 0 · slug 0 · domain 0` tiada dump dokumen atau output pengimbas.
   - `pelupusn=7`, `kelulusn=16`, `AJK=1`, `QR=1`, `ZIP=1`, `SLA=1`, `PDF=1`, `SPDM=0`, kawalan fallback `10–12`, dan Meili `penapis/lajur=0` hanya hidup dalam prosa; transkrip produksi hanya menyimpan 83, 11, 12, OCR 10, DDMS 0, XYZQ 0 dan `taip` 1.
   - Dakwaan dua ujian mutasi sengaja menjadi merah tiada log merah yang dikomit.
   - `PENEMUAN-CARIAN.md` masih menyatakan 23 assertion; larian semasa menghasilkan 33.

PENEMUAN SUBSTANTIF: 19
