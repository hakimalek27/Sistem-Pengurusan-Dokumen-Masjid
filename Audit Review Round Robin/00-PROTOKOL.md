# Protokol Round Robin — Audit & Review Diwan (SPDM)

**Sistem diaudit:** https://bakwim.my (produksi, Tencent Lighthouse 43.156.242.188, `/opt/diwan`)
**Repo:** `C:\Projek Coding\Sistem Pengurusan Dokumen Masjid` (jangan ubah kod)
**Peserta:** Claude (Claude Code) ↔ Codex (Cursor/Codex CLI)
**Arahan pemilik:** audit A–Z semua workflow/halaman/butang, desktop + mobile, sebagai penguji sebenar;
fokus khas isu penyelarasan (sync) Pembantu Bantuan/tour; tiada perubahan kod; cadangan penambahbaikan.

## Aliran giliran

```
Claude Pusingan 1 (audit UI penuh + laporan)
   → trigger → Codex Pusingan 2 (semak penemuan Claude + audit sendiri + laporan)
   → trigger → Claude Pusingan 3 (semak penemuan Codex + audit tambahan + laporan)
   → … ulang …
   → apabila 1 pusingan tiada penemuan baharu + kedua-dua sahkan liputan → FINAL-RUMUSAN.md
```

## Kewajipan setiap pusingan

1. Baca `STATUS.md` — pastikan giliran anda. Jika bukan, BERHENTI.
2. Baca SEMUA laporan pusingan terdahulu dalam folder ini.
3. **Sahkan atau tolak** setiap penemuan pusingan sebelumnya dengan bukti anda sendiri
   (jangan terima bulat-bulat) — rekod verdict: SAH / TIDAK SAH / TIDAK DAPAT DISAHKAN + bukti.
4. Jalankan audit tambahan anda sendiri (kawasan yang belum diliputi / sudut berbeza).
5. Tulis `PUSINGAN-NN-<EJEN>.md` — format di bawah.
6. Kemas kini `STATUS.md`: giliran seterusnya + baris log.
7. Trigger ejen seterusnya.

## Format laporan pusingan

```markdown
# Pusingan NN — <EJEN> — <tarikh masa>
## A. Semakan penemuan pusingan sebelumnya (verdict + bukti)
## B. Skop & kaedah pusingan ini
## C. Penemuan baharu (ID unik: RR-NN-XX, severiti: KRITIKAL/TINGGI/SEDERHANA/RENDAH/NOTA-UX)
   - Setiap satu: lokasi (URL/fail), langkah ulang, jangkaan vs sebenar, bukti
## D. Cadangan penambahbaikan (tanpa melaksana)
## E. Liputan (apa diuji, apa BELUM diuji — jujur)
## F. Status: SIAP PUSINGAN / perlu sambungan
```

## Sempadan keselamatan (WAJIB patuh)

- ⛔ JANGAN ubah/commit sebarang kod atau config produksi.
- ⛔ JANGAN sentuh data tenant sebenar `mamad` (baca metadata sahaja jika perlu; TIADA mutasi).
- ⛔ JANGAN dedahkan kata laluan/token/secret dalam laporan.
- ✅ Mutasi ujian dibenarkan HANYA pada tenant `smoke` + fixture `audit-*` sementara (dibersihkan di akhir).
- ✅ Akses server baca-sahaja untuk verifikasi (log, DB query SELECT) dibenarkan.
- Akaun ujian: pengguna `audit-*@smoke.test` dalam tenant `smoke` (Claude cipta & bersihkan;
  kelayakan dikongsi secara selamat di luar laporan — lihat `fixtures/AKAUN-UJIAN.md` jika perlu).

## Trigger

- **Claude → Codex:** Claude jalankan `codex exec` (CLI) dengan arahan pusingan + kemas `STATUS.md`.
- **Codex → Claude:** Codex kemas `STATUS.md` kepada "CLAUDE — Pusingan NN" + tulis laporannya.
  Claude memantau fail `STATUS.md`/laporan baharu secara automatik.
