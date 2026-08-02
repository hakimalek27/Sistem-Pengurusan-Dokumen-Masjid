# HELP-TARGETS — Registry Sasaran `data-help-target`

> **DIJANA** daripada `resources/help/targets.json` oleh
> `Audit Review Round Robin/bukti/plan-baseline/tools/generate-help-targets-doc.mjs` —
> JANGAN sunting fail ini secara tangan (PELAN-PEMBAIKAN.md §7.2 langkah 4).
> Registry ialah sumber kebenaran; ujian membaca registry, bukan grep sumber.

## Sasaran AKTIF (13) — dirujuk katalog; mesti unik + kelihatan dlm DOM route-nya

| ID | Family | Route | Sumber | Viewport | Prasyarat (`state`) | Permission |
|---|---|---|---|---|---|---|
| `inbox-upload` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:29` | both | - | inbox.view |
| `inbox-upload-modal` | workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:30` | both | modal:muat-naik terbuka | inbox.view |
| `inbox-classify` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:85` | both | jadual Peti Masuk mempunyai item belum diklasifikasi | inbox.classify |
| `classification-source` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:106` | both | modal:klasifikasi terbuka (wizard langkah 1) | inbox.classify |
| `classification-metadata` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:117` | both | modal:klasifikasi terbuka (wizard langkah 2) | inbox.classify |
| `classification-file` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:133` | both | modal:klasifikasi terbuka (wizard langkah 3) | inbox.classify |
| `classification-minit` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:172` | both | modal:klasifikasi terbuka (wizard langkah 4) | inbox.classify |
| `classification-review` | screen | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:193` | both | modal:klasifikasi terbuka (wizard langkah 5) | inbox.classify |
| `classification-submit` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:92` | both | modal:klasifikasi terbuka (wizard langkah 5) | inbox.classify |
| `registration-organisation` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:22` | both | wizard pendaftaran langkah 1 | - |
| `registration-admin` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:22` | both | wizard pendaftaran langkah 2 | - |
| `registration-consent` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:22` | both | wizard pendaftaran langkah 3 | - |
| `registration-complete` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:9` | both | permohonan berjaya dihantar | - |

## Sasaran RIZAB (5) — wujud dlm DOM, belum dirujuk katalog

| ID | Family | Route | Sumber | Viewport | Prasyarat (`state`) | Permission |
|---|---|---|---|---|---|---|
| `inbox-classification-modal` | screen | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:87` | both | modal:klasifikasi terbuka | inbox.classify |
| `registration-previous` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:83` | both | wizard pendaftaran langkah ≥2 | - |
| `registration-next` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:85` | both | wizard pendaftaran langkah 1-2 | - |
| `registration-submit` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:87` | both | wizard pendaftaran langkah 3 | - |
| `what-next` | tenant|admin | `/app/{tenant}|/admin` | `resources/views/filament/widgets/what-next.blade.php:2` | both | - | - |

- `inbox-classification-modal`: Wujud dlm DOM; sasaran rizab langkah orientasi (C12 — jangan guna dua kali berturut) (sejak 2026-08-02)
- `registration-previous`: Belum dirujuk katalog (sejak 2026-08-02)
- `registration-next`: Belum dirujuk katalog (diguna e2e sebagai kawalan navigasi) (sejak 2026-08-02)
- `registration-submit`: Belum dirujuk katalog (sejak 2026-08-02)
- `what-next`: Widget dashboard; calon sasaran F6 W5 (sejak 2026-08-02)

## Peraturan (gate registry §7.2)

1. Skema sah (ujian struktur) · setiap sasaran `active` **unik dan kelihatan** dalam render
   halaman `route`-nya pada `viewport` yang dinyatakan, selepas `state` disediakan.
2. Tahan morph Livewire (atribut datang dari HTML server).
3. Yatim dua hala = 0: setiap `target` bukan-generik dalam katalog wujud dalam registry;
   setiap entri registry dirujuk ≥1 guide ATAU bertanda `reserved`.
4. Penamaan: `{skrin}-{fungsi}` (cth `records-search`, `approvals-approve`).
5. `generic-justified` hanya melalui allowlist bersebab + bertarikh (manifest baseline).
