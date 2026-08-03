# HELP-TARGETS — Registry Sasaran `data-help-target`

> **DIJANA** daripada `resources/help/targets.json` oleh
> `Audit Review Round Robin/bukti/plan-baseline/tools/generate-help-targets-doc.mjs` —
> JANGAN sunting fail ini secara tangan (PELAN-PEMBAIKAN.md §7.2 langkah 4).
> Registry ialah sumber kebenaran; ujian membaca registry, bukan grep sumber.

## Sasaran AKTIF (22) — dirujuk katalog; mesti unik + kelihatan dlm DOM route-nya

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
| `disposal-warning` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:6` | both | - | disposal.view |
| `disposal-candidates` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:14` | both | - | disposal.view |
| `disposal-batches` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:19` | both | - | disposal.view |
| `favourites-list` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:4` | both | - | favourites.view |
| `favourite-item` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:8` | both | sentiasa (article pertama atau mesej kosong) | favourites.view |
| `login-identity` | public | `/log-masuk` | `resources/views/livewire/request-magic-link.blade.php:16` | both | borang belum dihantar | public |
| `login-submit` | public | `/log-masuk` | `resources/views/livewire/request-magic-link.blade.php:19` | both | borang belum dihantar | public |
| `inbox-upload-dropzone` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:47` | both | modal:muat-naik terbuka | records.create |
| `inbox-upload-submit` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:35` | both | modal:muat-naik terbuka | records.create |

## Sasaran RIZAB (9) — wujud dlm DOM, belum dirujuk katalog

| ID | Family | Route | Sumber | Viewport | Prasyarat (`state`) | Permission |
|---|---|---|---|---|---|---|
| `inbox-classification-modal` | screen | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:87` | both | modal:klasifikasi terbuka | inbox.classify |
| `registration-previous` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:83` | both | wizard pendaftaran langkah ≥2 | - |
| `registration-next` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:85` | both | wizard pendaftaran langkah 1-2 | - |
| `registration-submit` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:87` | both | wizard pendaftaran langkah 3 | - |
| `what-next` | tenant|admin | `/app/{tenant}|/admin` | `resources/views/filament/widgets/what-next.blade.php:2` | both | - | - |
| `disposal-status` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:26` | both | ada batch | disposal.view |
| `disposal-actions` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:28` | both | ada batch | disposal.execute |
| `favourite-open` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:9` | both | ada kegemaran | favourites.view |
| `favourite-remove` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:13` | both | ada kegemaran | favourites.view |

- `inbox-classification-modal`: Wujud dlm DOM; sasaran rizab langkah orientasi (C12 — jangan guna dua kali berturut) (sejak 2026-08-02)
- `registration-previous`: Belum dirujuk katalog (sejak 2026-08-02)
- `registration-next`: Belum dirujuk katalog (diguna e2e sebagai kawalan navigasi) (sejak 2026-08-02)
- `registration-submit`: Belum dirujuk katalog (sejak 2026-08-02)
- `what-next`: Widget dashboard; calon sasaran F6 W5 (sejak 2026-08-02)
- `disposal-status`: Ditambah F6-W0 sebagai penambat lajur; guide tenant.pelupusan v2 menyorot disposal-batches. Tersedia untuk gelombang W5. (sejak 2026-08-03)
- `disposal-actions`: Ditambah F6-W0 sebagai penambat lajur tindakan; belum dirujuk katalog. Tersedia untuk gelombang W5. (sejak 2026-08-03)
- `favourite-open`: Ditambah F6-W0; guide tenant.kegemaran v2 menyorot favourite-item yang membungkusnya. Tersedia untuk W5. (sejak 2026-08-03)
- `favourite-remove`: Ditambah F6-W0; butang buang di dalam favourite-item. Tersedia untuk W5. (sejak 2026-08-03)

## Peraturan (gate registry §7.2)

1. Skema sah (ujian struktur) · setiap sasaran `active` **unik dan kelihatan** dalam render
   halaman `route`-nya pada `viewport` yang dinyatakan, selepas `state` disediakan.
2. Tahan morph Livewire (atribut datang dari HTML server).
3. Yatim dua hala = 0: setiap `target` bukan-generik dalam katalog wujud dalam registry;
   setiap entri registry dirujuk ≥1 guide ATAU bertanda `reserved`.
4. Penamaan: `{skrin}-{fungsi}` (cth `records-search`, `approvals-approve`).
5. `generic-justified` hanya melalui allowlist bersebab + bertarikh (manifest baseline).
