# HELP-TARGETS — Registry Sasaran `data-help-target`

> **DIJANA** daripada `resources/help/targets.json` oleh
> `Audit Review Round Robin/bukti/plan-baseline/tools/generate-help-targets-doc.mjs` —
> JANGAN sunting fail ini secara tangan (PELAN-PEMBAIKAN.md §7.2 langkah 4).
> Registry ialah sumber kebenaran; ujian membaca registry, bukan grep sumber.

## Sasaran AKTIF (77) — dirujuk katalog; mesti unik + kelihatan dlm DOM route-nya

| ID | Family | Route | Sumber | Viewport | Prasyarat (`state`) | Permission |
|---|---|---|---|---|---|---|
| `members-invite` | screen | `/app/{tenant}/ahli-peranan` | `app/Filament/App/Pages/AhliPeranan.php:138` | both | - | users.manage |
| `members-invite-email` | screen | `/app/{tenant}/ahli-peranan` | `app/Filament/App/Pages/AhliPeranan.php:149` | both | modal:Jemput Ahli terbuka | users.manage |
| `members-invite-phone` | screen | `/app/{tenant}/ahli-peranan` | `app/Filament/App/Pages/AhliPeranan.php:146` | both | modal:Jemput Ahli terbuka | users.manage |
| `members-invite-role` | screen | `/app/{tenant}/ahli-peranan` | `app/Filament/App/Pages/AhliPeranan.php:152` | both | modal:Jemput Ahli terbuka | users.manage |
| `members-invite-submit` | screen | `/app/{tenant}/ahli-peranan` | `app/Filament/App/Pages/AhliPeranan.php:140` | both | modal:Jemput Ahli terbuka | users.manage |
| `search-favourite` | screen | `/app/{tenant}/carian` | `resources/views/filament/app/pages/cari-rekod.blade.php:77` | both | - | records.view |
| `search-filters` | screen | `/app/{tenant}/carian` | `resources/views/filament/app/pages/cari-rekod.blade.php:27` | both | - | records.view |
| `search-result-item` | screen | `/app/{tenant}/carian` | `resources/views/filament/app/pages/cari-rekod.blade.php:70` | both | - | records.view |
| `search-result-open` | screen | `/app/{tenant}/carian` | `resources/views/filament/app/pages/cari-rekod.blade.php:71` | both | - | records.view |
| `search-results` | screen | `/app/{tenant}/carian` | `resources/views/filament/app/pages/cari-rekod.blade.php:64` | both | - | records.view |
| `classnode-code` | screen | `/app/{tenant}/classification-nodes/create` | `app/Filament/App/Resources/ClassificationNodes/Schemas/ClassificationNodeForm.php:48` | both | - | classification.manage |
| `classnode-level` | screen | `/app/{tenant}/classification-nodes/create` | `app/Filament/App/Resources/ClassificationNodes/Schemas/ClassificationNodeForm.php:42` | both | - | classification.manage |
| `classnode-parent` | screen | `/app/{tenant}/classification-nodes/create` | `app/Filament/App/Resources/ClassificationNodes/Schemas/ClassificationNodeForm.php:32` | both | - | classification.manage |
| `classnode-sensitivity` | screen | `/app/{tenant}/classification-nodes/create` | `app/Filament/App/Resources/ClassificationNodes/Schemas/ClassificationNodeForm.php:58` | both | - | classification.manage |
| `classnode-submit` | screen | `/app/{tenant}/classification-nodes/create` | `app/Filament/App/Resources/ClassificationNodes/Pages/CreateClassificationNode.php:22` | both | - | classification.manage |
| `classnode-title` | screen | `/app/{tenant}/classification-nodes/create` | `app/Filament/App/Resources/ClassificationNodes/Schemas/ClassificationNodeForm.php:52` | both | - | classification.manage |
| `delegation-capabilities` | screen | `/app/{tenant}/delegasi/create` | `app/Filament/App/Resources/Delegations/Schemas/DelegationForm.php:23` | both | - | delegations.manage |
| `delegation-delegate` | screen | `/app/{tenant}/delegasi/create` | `app/Filament/App/Resources/Delegations/Schemas/DelegationForm.php:21` | both | - | delegations.manage |
| `delegation-principal` | screen | `/app/{tenant}/delegasi/create` | `app/Filament/App/Resources/Delegations/Schemas/DelegationForm.php:19` | both | - | delegations.manage |
| `delegation-reason` | screen | `/app/{tenant}/delegasi/create` | `app/Filament/App/Resources/Delegations/Schemas/DelegationForm.php:29` | both | - | delegations.manage |
| `delegation-starts` | screen | `/app/{tenant}/delegasi/create` | `app/Filament/App/Resources/Delegations/Schemas/DelegationForm.php:25` | both | - | delegations.manage |
| `delegation-submit` | screen | `/app/{tenant}/delegasi/create` | `app/Filament/App/Resources/Delegations/Pages/CreateDelegation.php:21` | both | - | delegations.manage |
| `favourite-item` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:8` | both | sentiasa (article pertama atau mesej kosong) | favourites.view |
| `favourites-list` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:4` | both | - | favourites.view |
| `disposal-batches` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:19` | both | - | disposal.view |
| `disposal-candidates` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:14` | both | - | disposal.view |
| `disposal-confirm` | screen | `/app/{tenant}/pelupusan` | `app/Filament/App/Pages/PelupusanManual.php:96` | both | modal:Sedia Senarai Semakan terbuka | disposal.prepare |
| `disposal-prepare` | screen|tenant | `/app/{tenant}/pelupusan` | `app/Filament/App/Pages/PelupusanManual.php:94` | both | - | disposal.prepare |
| `disposal-records` | screen | `/app/{tenant}/pelupusan` | `app/Filament/App/Pages/PelupusanManual.php:104` | both | modal:Sedia Senarai Semakan terbuka | disposal.prepare |
| `disposal-warning` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:6` | both | - | disposal.view |
| `storage-add` | screen | `/app/{tenant}/penggunaan` | `app/Filament/App/Pages/PenggunaanStoran.php:51` | both | - | storage.order |
| `storage-orders` | screen | `/app/{tenant}/penggunaan` | `resources/views/filament/app/pages/penggunaan-storan.blade.php:43` | both | - | - |
| `storage-submit` | screen | `/app/{tenant}/penggunaan` | `app/Filament/App/Pages/PenggunaanStoran.php:53` | both | modal:Tambah Storan terbuka | storage.order |
| `storage-usage` | screen | `/app/{tenant}/penggunaan` | `resources/views/filament/app/pages/penggunaan-storan.blade.php:6` | both | - | - |
| `classification-file` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:133` | both | modal:klasifikasi terbuka (wizard langkah 3) | inbox.classify |
| `classification-metadata` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:117` | both | modal:klasifikasi terbuka (wizard langkah 2) | inbox.classify |
| `classification-minit` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:172` | both | modal:klasifikasi terbuka (wizard langkah 4) | inbox.classify |
| `classification-review` | screen | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:193` | both | modal:klasifikasi terbuka (wizard langkah 5) | inbox.classify |
| `classification-source` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:106` | both | modal:klasifikasi terbuka (wizard langkah 1) | inbox.classify |
| `classification-submit` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:92` | both | modal:klasifikasi terbuka (wizard langkah 5) | inbox.classify |
| `inbox-classify` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:85` | both | jadual Peti Masuk mempunyai item belum diklasifikasi | inbox.classify |
| `inbox-upload` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:29` | both | - | inbox.view |
| `inbox-upload-dropzone` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:47` | both | modal:muat-naik terbuka | records.create |
| `inbox-upload-modal` | workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:30` | both | modal:muat-naik terbuka | inbox.view |
| `inbox-upload-submit` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Pages/ListInbox.php:35` | both | modal:muat-naik terbuka | records.create |
| `profil-akaun` | screen | `/app/{tenant}/profil` | `resources/views/filament/app/pages/profil.blade.php:4` | both | - | - |
| `profil-kata-laluan` | screen | `/app/{tenant}/profil` | `app/Filament/Concerns/ProfileActions.php:95` | both | - | - |
| `profil-notifikasi` | screen | `/app/{tenant}/profil` | `app/Filament/Concerns/ProfileActions.php:32` | both | - | - |
| `profil-notifikasi-save` | screen | `/app/{tenant}/profil` | `app/Filament/Concerns/ProfileActions.php:34` | both | modal:Tetapan Notifikasi terbuka | - |
| `profil-password-confirm` | screen | `/app/{tenant}/profil` | `app/Filament/Concerns/ProfileActions.php:114` | both | modal:Tetapkan Kata Laluan terbuka | - |
| `profil-password-save` | screen | `/app/{tenant}/profil` | `app/Filament/Concerns/ProfileActions.php:97` | both | modal:Tetapkan Kata Laluan terbuka | - |
| `profil-ujian` | screen | `/app/{tenant}/profil` | `app/Filament/Concerns/ProfileActions.php:50` | both | - | - |
| `record-correction` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:62` | both | detail:records - halaman butiran rekod dibuka | records.view |
| `record-correction-reason` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:68` | both | detail:records + modal:Mohon Pembetulan terbuka | records.view |
| `record-correction-submit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:64` | both | detail:records + modal:Mohon Pembetulan terbuka | records.view |
| `record-correction-title` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:70` | both | detail:records + modal:Mohon Pembetulan terbuka | records.view |
| `regfile-medium` | screen | `/app/{tenant}/registry-files/create` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileForm.php:39` | both | - | files.manage |
| `regfile-node` | screen | `/app/{tenant}/registry-files/create` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileForm.php:31` | both | - | files.manage |
| `regfile-physical` | screen | `/app/{tenant}/registry-files/create` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileForm.php:42` | both | - | files.manage |
| `regfile-submit` | screen | `/app/{tenant}/registry-files/create` | `app/Filament/App/Resources/RegistryFiles/Pages/CreateRegistryFile.php:22` | both | - | files.manage |
| `regfile-title` | screen | `/app/{tenant}/registry-files/create` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileForm.php:35` | both | - | files.manage |
| `retention-action` | screen | `/app/{tenant}/retensi-peraturan/create` | `app/Filament/App/Resources/RetentionRules/RetentionRuleResource.php:62` | both | - | retention.manage |
| `retention-note` | screen | `/app/{tenant}/retensi-peraturan/create` | `app/Filament/App/Resources/RetentionRules/RetentionRuleResource.php:71` | both | - | retention.manage |
| `retention-record-type` | screen | `/app/{tenant}/retensi-peraturan/create` | `app/Filament/App/Resources/RetentionRules/RetentionRuleResource.php:55` | both | - | retention.manage |
| `retention-submit` | screen | `/app/{tenant}/retensi-peraturan/create` | `app/Filament/App/Resources/RetentionRules/Pages/CreateRetentionRule.php:33` | both | - | retention.manage |
| `retention-years` | screen | `/app/{tenant}/retensi-peraturan/create` | `app/Filament/App/Resources/RetentionRules/RetentionRuleResource.php:60` | both | - | retention.manage |
| `mosque-settings-channels` | screen | `/app/{tenant}/tetapan-masjid` | `app/Filament/App/Pages/TetapanMasjid.php:84` | both | modal:Sunting Tetapan terbuka | mosque.settings |
| `mosque-settings-edit` | screen | `/app/{tenant}/tetapan-masjid` | `app/Filament/App/Pages/TetapanMasjid.php:63` | both | - | mosque.settings |
| `mosque-settings-keyword` | screen | `/app/{tenant}/tetapan-masjid` | `app/Filament/App/Pages/TetapanMasjid.php:82` | both | modal:Sunting Tetapan terbuka | mosque.settings |
| `mosque-settings-save` | screen | `/app/{tenant}/tetapan-masjid` | `app/Filament/App/Pages/TetapanMasjid.php:65` | both | modal:Sunting Tetapan terbuka | mosque.settings |
| `mosque-settings-senders` | screen | `/app/{tenant}/tetapan-masjid` | `app/Filament/App/Pages/TetapanMasjid.php:91` | both | modal:Sunting Tetapan terbuka | mosque.settings |
| `registration-admin` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:22` | both | wizard pendaftaran langkah 2 | - |
| `registration-complete` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:9` | both | permohonan berjaya dihantar | - |
| `registration-consent` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:22` | both | wizard pendaftaran langkah 3 | - |
| `registration-organisation` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:22` | both | wizard pendaftaran langkah 1 | - |
| `login-identity` | public | `/log-masuk` | `resources/views/livewire/request-magic-link.blade.php:16` | both | borang belum dihantar | public |
| `login-submit` | public | `/log-masuk` | `resources/views/livewire/request-magic-link.blade.php:19` | both | borang belum dihantar | public |

## Sasaran RIZAB (32) — wujud dlm DOM, belum dirujuk katalog

| ID | Family | Route | Sumber | Viewport | Prasyarat (`state`) | Permission |
|---|---|---|---|---|---|---|
| `members-invite-name` | screen | `/app/{tenant}/ahli-peranan` | `app/Filament/App/Pages/AhliPeranan.php:144` | both | modal:Jemput Ahli terbuka | users.manage |
| `delegation-ends` | screen | `/app/{tenant}/delegasi/create` | `app/Filament/App/Resources/Delegations/Schemas/DelegationForm.php:27` | both | - | delegations.manage |
| `favourite-open` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:9` | both | ada kegemaran | favourites.view |
| `favourite-remove` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:13` | both | ada kegemaran | favourites.view |
| `disposal-actions` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:28` | both | ada batch | disposal.execute |
| `disposal-status` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:26` | both | ada batch | disposal.view |
| `storage-blocks` | screen | `/app/{tenant}/penggunaan` | `app/Filament/App/Pages/PenggunaanStoran.php:60` | both | modal:Tambah Storan terbuka | storage.order |
| `inbox-classification-modal` | screen | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:87` | both | modal:klasifikasi terbuka | inbox.classify |
| `profil-password` | screen | `/app/{tenant}/profil` | `app/Filament/Concerns/ProfileActions.php:107` | both | modal:Tetapkan Kata Laluan terbuka | - |
| `record-approval` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:116` | both | detail:records | approvals.request |
| `record-approval-approver` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:123` | both | detail:records + modal:Mohon Kelulusan terbuka | approvals.request |
| `record-approval-note` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:125` | both | detail:records + modal:Mohon Kelulusan terbuka | approvals.request |
| `record-approval-submit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:118` | both | detail:records + modal:Mohon Kelulusan terbuka | approvals.request |
| `record-minit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:92` | both | detail:records | minit.create |
| `record-minit-action` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:99` | both | detail:records + modal:Edarkan Minit terbuka | minit.create |
| `record-minit-body` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:103` | both | detail:records + modal:Edarkan Minit terbuka | minit.create |
| `record-minit-cc` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:101` | both | detail:records + modal:Edarkan Minit terbuka | minit.create |
| `record-minit-priority` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:105` | both | detail:records + modal:Edarkan Minit terbuka | minit.create |
| `record-minit-submit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:94` | both | detail:records + modal:Edarkan Minit terbuka | minit.create |
| `record-move` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:170` | both | detail:records | records.move |
| `record-move-file` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:178` | both | detail:records + modal:Pindah Fail terbuka | records.move |
| `record-move-reason` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:181` | both | detail:records + modal:Pindah Fail terbuka | records.move |
| `record-move-submit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:172` | both | detail:records + modal:Pindah Fail terbuka | records.move |
| `record-version` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:136` | both | detail:records | records.supersede |
| `record-version-file` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:146` | both | detail:records + modal:Ganti Versi terbuka | records.supersede |
| `record-version-submit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:138` | both | detail:records + modal:Ganti Versi terbuka | records.supersede |
| `regfile-location` | screen | `/app/{tenant}/registry-files/create` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileForm.php:44` | both | - | files.manage |
| `retention-prefix` | screen | `/app/{tenant}/retensi-peraturan/create` | `app/Filament/App/Resources/RetentionRules/RetentionRuleResource.php:58` | both | - | retention.manage |
| `what-next` | tenant|admin | `/app/{tenant}|/admin` | `resources/views/filament/widgets/what-next.blade.php:2` | both | - | - |
| `registration-next` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:85` | both | wizard pendaftaran langkah 1-2 | - |
| `registration-previous` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:83` | both | wizard pendaftaran langkah ≥2 | - |
| `registration-submit` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:87` | both | wizard pendaftaran langkah 3 | - |

- `members-invite-name`: - (sejak 2026-08-04)
- `delegation-ends`: - (sejak 2026-08-04)
- `favourite-open`: Ditambah F6-W0; guide tenant.kegemaran v2 menyorot favourite-item yang membungkusnya. Tersedia untuk W5. (sejak 2026-08-03)
- `favourite-remove`: Ditambah F6-W0; butang buang di dalam favourite-item. Tersedia untuk W5. (sejak 2026-08-03)
- `disposal-actions`: Ditambah F6-W0 sebagai penambat lajur tindakan; belum dirujuk katalog. Tersedia untuk gelombang W5. (sejak 2026-08-03)
- `disposal-status`: Ditambah F6-W0 sebagai penambat lajur; guide tenant.pelupusan v2 menyorot disposal-batches. Tersedia untuk gelombang W5. (sejak 2026-08-03)
- `storage-blocks`: - (sejak 2026-08-04)
- `inbox-classification-modal`: Wujud dlm DOM; sasaran rizab langkah orientasi (C12 — jangan guna dua kali berturut) (sejak 2026-08-02)
- `profil-password`: - (sejak 2026-08-04)
- `record-approval`: - (sejak 2026-08-04)
- `record-approval-approver`: - (sejak 2026-08-04)
- `record-approval-note`: - (sejak 2026-08-04)
- `record-approval-submit`: - (sejak 2026-08-04)
- `record-minit`: - (sejak 2026-08-04)
- `record-minit-action`: - (sejak 2026-08-04)
- `record-minit-body`: - (sejak 2026-08-04)
- `record-minit-cc`: - (sejak 2026-08-04)
- `record-minit-priority`: - (sejak 2026-08-04)
- `record-minit-submit`: - (sejak 2026-08-04)
- `record-move`: - (sejak 2026-08-04)
- `record-move-file`: - (sejak 2026-08-04)
- `record-move-reason`: - (sejak 2026-08-04)
- `record-move-submit`: - (sejak 2026-08-04)
- `record-version`: - (sejak 2026-08-04)
- `record-version-file`: - (sejak 2026-08-04)
- `record-version-submit`: - (sejak 2026-08-04)
- `regfile-location`: - (sejak 2026-08-04)
- `retention-prefix`: - (sejak 2026-08-04)
- `what-next`: Widget dashboard; calon sasaran F6 W5 (sejak 2026-08-02)
- `registration-next`: Belum dirujuk katalog (diguna e2e sebagai kawalan navigasi) (sejak 2026-08-02)
- `registration-previous`: Belum dirujuk katalog (sejak 2026-08-02)
- `registration-submit`: Belum dirujuk katalog (sejak 2026-08-02)

## Peraturan (gate registry §7.2)

1. Skema sah (ujian struktur) · setiap sasaran `active` **unik dan kelihatan** dalam render
   halaman `route`-nya pada `viewport` yang dinyatakan, selepas `state` disediakan.
2. Tahan morph Livewire (atribut datang dari HTML server).
3. Yatim dua hala = 0: setiap `target` bukan-generik dalam katalog wujud dalam registry;
   setiap entri registry dirujuk ≥1 guide ATAU bertanda `reserved`.
4. Penamaan: `{skrin}-{fungsi}` (cth `records-search`, `approvals-approve`).
5. `generic-justified` hanya melalui allowlist bersebab + bertarikh (manifest baseline).
