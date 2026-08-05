# HELP-TARGETS — Registry Sasaran `data-help-target`

> **DIJANA** daripada `resources/help/targets.json` oleh
> `Audit Review Round Robin/bukti/plan-baseline/tools/generate-help-targets-doc.mjs` —
> JANGAN sunting fail ini secara tangan (PELAN-PEMBAIKAN.md §7.2 langkah 4).
> Registry ialah sumber kebenaran; ujian membaca registry, bukan grep sumber.

## Sasaran AKTIF (147) — dirujuk katalog; mesti unik + kelihatan dlm DOM route-nya

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
| `approval-lulus` | screen | `/app/{tenant}/kelulusan` | `app/Filament/App/Resources/Approvals/Tables/ApprovalsTable.php:59` | both | jadual tidak kosong (baris pertama, status menunggu) | approvals.decide |
| `approval-note` | screen | `/app/{tenant}/kelulusan` | `app/Filament/App/Resources/Approvals/Tables/ApprovalsTable.php:69` | both | modal:Lulus atau Tolak terbuka | approvals.decide |
| `approval-password` | screen | `/app/{tenant}/kelulusan` | `app/Filament/App/Resources/Approvals/Tables/ApprovalsTable.php:67` | both | modal:Lulus atau Tolak terbuka | approvals.decide |
| `approval-record` | screen | `/app/{tenant}/kelulusan` | `app/Filament/App/Resources/Approvals/Tables/ApprovalsTable.php:26` | both | jadual tidak kosong (sel baris pertama) | approvals.decide |
| `approval-status` | screen | `/app/{tenant}/kelulusan` | `app/Filament/App/Resources/Approvals/Tables/ApprovalsTable.php:31` | both | jadual tidak kosong (sel baris pertama) | approvals.decide |
| `approval-submit` | screen | `/app/{tenant}/kelulusan` | `app/Filament/App/Resources/Approvals/Tables/ApprovalsTable.php:61` | both | modal:Lulus atau Tolak terbuka | approvals.decide |
| `log-actor` | screen | `/app/{tenant}/log-aktiviti` | `resources/views/filament/app/activity-log-details.blade.php:7` | both | modal:Butiran Log Aktiviti terbuka | audit.view |
| `log-detail` | screen | `/app/{tenant}/log-aktiviti` | `app/Filament/App/Resources/MosqueActivityLogs/Tables/MosqueActivityLogsTable.php:110` | both | jadual tidak kosong (baris pertama) | audit.view |
| `log-metadata` | screen | `/app/{tenant}/log-aktiviti` | `resources/views/filament/app/activity-log-details.blade.php:54` | both | modal:Butiran Log Aktiviti terbuka | audit.view |
| `log-record` | screen | `/app/{tenant}/log-aktiviti` | `resources/views/filament/app/activity-log-details.blade.php:20` | both | modal:Butiran Log Aktiviti terbuka | audit.view |
| `log-source` | screen | `/app/{tenant}/log-aktiviti` | `resources/views/filament/app/activity-log-details.blade.php:34` | both | modal:Butiran Log Aktiviti terbuka | audit.view |
| `minit-complete` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:72` | both | jadual tidak kosong (baris pertama) | minit.view |
| `minit-complete-confirm` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:74` | both | modal:Tanda Selesai terbuka | minit.view |
| `minit-reply` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:86` | both | jadual tidak kosong (baris pertama) | minit.view |
| `minit-reply-action` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:94` | both | modal:Balas & Edarkan terbuka | minit.view |
| `minit-reply-body` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:99` | both | modal:Balas & Edarkan terbuka | minit.view |
| `minit-reply-cc` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:97` | both | modal:Balas & Edarkan terbuka | minit.view |
| `minit-reply-priority` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:102` | both | modal:Balas & Edarkan terbuka | minit.view |
| `minit-reply-submit` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:88` | both | modal:Balas & Edarkan terbuka | minit.view |
| `minit-record` | workflow | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:48` | both | jadual tidak kosong (sel baris pertama) | minit.view |
| `minit-status` | screen | `/app/{tenant}/minit-saya` | `app/Filament/App/Resources/Minits/Tables/MinitsTable.php:38` | both | jadual tidak kosong (sel baris pertama) | minit.view |
| `disposal-batches` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:19` | both | - | disposal.view |
| `disposal-candidates` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:14` | both | - | disposal.view |
| `disposal-confirm` | screen | `/app/{tenant}/pelupusan` | `app/Filament/App/Pages/PelupusanManual.php:96` | both | modal:Sedia Senarai Semakan terbuka | disposal.prepare |
| `disposal-prepare` | screen|tenant | `/app/{tenant}/pelupusan` | `app/Filament/App/Pages/PelupusanManual.php:94` | both | - | disposal.prepare |
| `disposal-records` | screen | `/app/{tenant}/pelupusan` | `app/Filament/App/Pages/PelupusanManual.php:104` | both | modal:Sedia Senarai Semakan terbuka | disposal.prepare |
| `disposal-warning` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:6` | both | - | disposal.view |
| `storage-add` | screen | `/app/{tenant}/penggunaan` | `app/Filament/App/Pages/PenggunaanStoran.php:51` | both | - | storage.order |
| `storage-blocks` | screen | `/app/{tenant}/penggunaan` | `app/Filament/App/Pages/PenggunaanStoran.php:60` | both | modal:Tambah Storan terbuka | storage.order |
| `storage-orders` | screen | `/app/{tenant}/penggunaan` | `resources/views/filament/app/pages/penggunaan-storan.blade.php:43` | both | - | - |
| `storage-submit` | screen | `/app/{tenant}/penggunaan` | `app/Filament/App/Pages/PenggunaanStoran.php:53` | both | modal:Tambah Storan terbuka | storage.order |
| `storage-usage` | screen | `/app/{tenant}/penggunaan` | `resources/views/filament/app/pages/penggunaan-storan.blade.php:6` | both | - | - |
| `onboarding-members` | screen | `/app/{tenant}/persediaan` | `app/Filament/App/Pages/OnboardingWizard.php:107` | both | wizard langkah 3 | mosque.settings |
| `onboarding-phone` | screen | `/app/{tenant}/persediaan` | `app/Filament/App/Pages/OnboardingWizard.php:88` | both | wizard langkah 2 | mosque.settings |
| `onboarding-start` | screen | `/app/{tenant}/persediaan` | `app/Filament/App/Pages/OnboardingWizard.php:60` | both | - | mosque.settings |
| `onboarding-submit` | screen | `/app/{tenant}/persediaan` | `app/Filament/App/Pages/OnboardingWizard.php:62` | both | wizard langkah 4 | mosque.settings |
| `onboarding-wa-source` | screen | `/app/{tenant}/persediaan` | `app/Filament/App/Pages/OnboardingWizard.php:92` | both | wizard langkah 2 | mosque.settings |
| `classification-file` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:133` | both | modal:klasifikasi terbuka (wizard langkah 3) | inbox.classify |
| `classification-metadata` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:117` | both | modal:klasifikasi terbuka (wizard langkah 2) | inbox.classify |
| `classification-minit` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:172` | both | modal:klasifikasi terbuka (wizard langkah 4) | inbox.classify |
| `classification-review` | screen | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:193` | both | modal:klasifikasi terbuka (wizard langkah 5) | inbox.classify |
| `classification-source` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:106` | both | modal:klasifikasi terbuka (wizard langkah 1) | inbox.classify |
| `classification-submit` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:92` | both | modal:klasifikasi terbuka (wizard langkah 5) | inbox.classify |
| `inbox-classify` | screen|workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:85` | both | jadual Peti Masuk mempunyai item belum diklasifikasi | inbox.classify |
| `inbox-scan-status` | workflow | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:63` | both | jadual Peti Masuk tidak kosong (sel baris pertama) | inbox.view |
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
| `page-actions` | screen | `/app/{tenant}/records` | `resources/js/help.js:decorateTargets` | both | detail:records | - |
| `record-approval` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:116` | both | detail:records | approvals.request |
| `record-approval-approver` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:123` | both | detail:records + modal:Mohon Kelulusan terbuka | approvals.request |
| `record-approval-note` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:125` | both | detail:records + modal:Mohon Kelulusan terbuka | approvals.request |
| `record-approval-submit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:118` | both | detail:records + modal:Mohon Kelulusan terbuka | approvals.request |
| `record-correction` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:62` | both | detail:records - halaman butiran rekod dibuka | records.view |
| `record-correction-reason` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:68` | both | detail:records + modal:Mohon Pembetulan terbuka | records.view |
| `record-correction-submit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:64` | both | detail:records + modal:Mohon Pembetulan terbuka | records.view |
| `record-correction-title` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:70` | both | detail:records + modal:Mohon Pembetulan terbuka | records.view |
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
| `record-tab-approval` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Schemas/RecordInfolist.php:93` | both | detail:records | records.view |
| `record-tab-attachments` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Schemas/RecordInfolist.php:57` | both | detail:records | records.view |
| `record-tab-audit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Schemas/RecordInfolist.php:112` | both | detail:records | records.view |
| `record-tab-info` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Schemas/RecordInfolist.php:25` | both | detail:records | records.view |
| `record-tab-minit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Schemas/RecordInfolist.php:65` | both | detail:records | records.view |
| `record-tab-ocr` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Schemas/RecordInfolist.php:49` | both | detail:records | records.view |
| `record-version` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:136` | both | detail:records | records.supersede |
| `record-version-file` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:146` | both | detail:records + modal:Ganti Versi terbuka | records.supersede |
| `record-version-submit` | screen | `/app/{tenant}/records` | `app/Filament/App/Resources/Records/Pages/ViewRecord.php:138` | both | detail:records + modal:Ganti Versi terbuka | records.supersede |
| `file-access-grant` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/RelationManagers/AccessGrantsRelationManager.php:60` | both | detail:registry-files | files.manage |
| `file-access-member` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/RelationManagers/AccessGrantsRelationManager.php:31` | both | modal:Beri Akses terbuka | files.manage |
| `file-access-revoke` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/RelationManagers/AccessGrantsRelationManager.php:72` | both | detail:registry-files + sekurang-kurangnya satu geran | files.manage |
| `file-access-submit` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/RelationManagers/AccessGrantsRelationManager.php:62` | both | modal:Beri Akses terbuka | files.manage |
| `file-checkout` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:29` | both | detail:registry-files + medium fizikal/hibrid | files.track |
| `file-checkout-due` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:40` | both | modal:Keluarkan Fail terbuka | files.track |
| `file-checkout-holder` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:35` | both | modal:Keluarkan Fail terbuka | files.track |
| `file-checkout-location` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:38` | both | modal:Keluarkan Fail terbuka | files.track |
| `file-checkout-notes` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:42` | both | modal:Keluarkan Fail terbuka | files.track |
| `file-checkout-submit` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:31` | both | modal:Keluarkan Fail terbuka | files.track |
| `file-custody` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileInfolist.php:26` | both | detail:registry-files | files.view |
| `file-identity` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileInfolist.php:15` | both | detail:registry-files | files.view |
| `file-medium` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileInfolist.php:23` | both | detail:registry-files | files.view |
| `file-movements` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileInfolist.php:29` | both | detail:registry-files | files.view |
| `file-relocate` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:55` | both | detail:registry-files + medium fizikal/hibrid | files.track |
| `file-relocate-location` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:61` | both | modal:Pindah Lokasi terbuka | files.track |
| `file-relocate-notes` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:63` | both | modal:Pindah Lokasi terbuka | files.track |
| `file-relocate-submit` | screen | `/app/{tenant}/registry-files` | `app/Filament/App/Resources/RegistryFiles/Pages/ViewRegistryFile.php:57` | both | modal:Pindah Lokasi terbuka | files.track |
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

## Sasaran RIZAB (22) — wujud dlm DOM, belum dirujuk katalog

| ID | Family | Route | Sumber | Viewport | Prasyarat (`state`) | Permission |
|---|---|---|---|---|---|---|
| `members-invite-name` | screen | `/app/{tenant}/ahli-peranan` | `app/Filament/App/Pages/AhliPeranan.php:144` | both | modal:Jemput Ahli terbuka | users.manage |
| `delegation-ends` | screen | `/app/{tenant}/delegasi/create` | `app/Filament/App/Resources/Delegations/Schemas/DelegationForm.php:27` | both | - | delegations.manage |
| `favourite-open` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:9` | both | ada kegemaran | favourites.view |
| `favourite-remove` | tenant | `/app/{tenant}/kegemaran` | `resources/views/filament/app/pages/kegemaran.blade.php:13` | both | ada kegemaran | favourites.view |
| `approval-tolak` | screen | `/app/{tenant}/kelulusan` | `app/Filament/App/Resources/Approvals/Tables/ApprovalsTable.php:59` | both | jadual tidak kosong (baris pertama, status menunggu) | approvals.decide |
| `disposal-actions` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:28` | both | ada batch | disposal.execute |
| `disposal-status` | tenant | `/app/{tenant}/pelupusan` | `resources/views/filament/app/pages/pelupusan-manual.blade.php:26` | both | ada batch | disposal.view |
| `onboarding-jawatan` | screen | `/app/{tenant}/persediaan` | `app/Filament/App/Pages/OnboardingWizard.php:75` | both | wizard langkah 1 | mosque.settings |
| `inbox-classification-modal` | screen | `/app/{tenant}/peti-masuk` | `app/Filament/App/Resources/Inbox/Tables/InboxTable.php:87` | both | modal:klasifikasi terbuka | inbox.classify |
| `profil-password` | screen | `/app/{tenant}/profil` | `app/Filament/Concerns/ProfileActions.php:107` | both | modal:Tetapkan Kata Laluan terbuka | - |
| `regfile-location` | screen | `/app/{tenant}/registry-files/create` | `app/Filament/App/Resources/RegistryFiles/Schemas/RegistryFileForm.php:44` | both | - | files.manage |
| `retention-prefix` | screen | `/app/{tenant}/retensi-peraturan/create` | `app/Filament/App/Resources/RetentionRules/RetentionRuleResource.php:58` | both | - | retention.manage |
| `what-next` | tenant|admin | `/app/{tenant}|/admin` | `resources/views/filament/widgets/what-next.blade.php:2` | both | - | - |
| `registration-next` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:85` | both | wizard pendaftaran langkah 1-2 | - |
| `registration-previous` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:83` | both | wizard pendaftaran langkah ≥2 | - |
| `registration-submit` | public | `/daftar` | `resources/views/livewire/register-mosque.blade.php:87` | both | wizard pendaftaran langkah 3 | - |
| `viewer-download` | screen | `/viewer/{media}` | `resources/views/document-viewer.blade.php:51` | both | halaman viewer TIADA runtime bantuan — rujuk nota F7 | records.view |
| `viewer-find` | screen | `/viewer/{media}` | `resources/views/document-viewer.blade.php:47` | both | halaman viewer TIADA runtime bantuan — rujuk nota F7 | records.view |
| `viewer-page-input` | screen | `/viewer/{media}` | `resources/views/document-viewer.blade.php:40` | both | halaman viewer TIADA runtime bantuan — rujuk nota F7 | records.view |
| `viewer-page-prev` | screen | `/viewer/{media}` | `resources/views/document-viewer.blade.php:39` | both | halaman viewer TIADA runtime bantuan — rujuk nota F7 | records.view |
| `viewer-print` | screen | `/viewer/{media}` | `resources/views/document-viewer.blade.php:50` | both | halaman viewer TIADA runtime bantuan — rujuk nota F7 | records.view |
| `viewer-zoom-in` | screen | `/viewer/{media}` | `resources/views/document-viewer.blade.php:45` | both | halaman viewer TIADA runtime bantuan — rujuk nota F7 | records.view |

- `members-invite-name`: - (sejak 2026-08-04)
- `delegation-ends`: - (sejak 2026-08-04)
- `favourite-open`: Ditambah F6-W0; guide tenant.kegemaran v2 menyorot favourite-item yang membungkusnya. Tersedia untuk W5. (sejak 2026-08-03)
- `favourite-remove`: Ditambah F6-W0; butang buang di dalam favourite-item. Tersedia untuk W5. (sejak 2026-08-03)
- `approval-tolak`: - (sejak 2026-08-04)
- `disposal-actions`: Ditambah F6-W0 sebagai penambat lajur tindakan; belum dirujuk katalog. Tersedia untuk gelombang W5. (sejak 2026-08-03)
- `disposal-status`: Ditambah F6-W0 sebagai penambat lajur; guide tenant.pelupusan v2 menyorot disposal-batches. Tersedia untuk gelombang W5. (sejak 2026-08-03)
- `onboarding-jawatan`: - (sejak 2026-08-04)
- `inbox-classification-modal`: Wujud dlm DOM; sasaran rizab langkah orientasi (C12 — jangan guna dua kali berturut) (sejak 2026-08-02)
- `profil-password`: - (sejak 2026-08-04)
- `regfile-location`: - (sejak 2026-08-04)
- `retention-prefix`: - (sejak 2026-08-04)
- `what-next`: Widget dashboard; calon sasaran F6 W5 (sejak 2026-08-02)
- `registration-next`: Belum dirujuk katalog (diguna e2e sebagai kawalan navigasi) (sejak 2026-08-02)
- `registration-previous`: Belum dirujuk katalog (sejak 2026-08-02)
- `registration-submit`: Belum dirujuk katalog (sejak 2026-08-02)
- `viewer-download`: - (sejak 2026-08-04)
- `viewer-find`: - (sejak 2026-08-04)
- `viewer-page-input`: - (sejak 2026-08-04)
- `viewer-page-prev`: - (sejak 2026-08-04)
- `viewer-print`: - (sejak 2026-08-04)
- `viewer-zoom-in`: - (sejak 2026-08-04)

## Peraturan (gate registry §7.2)

1. Skema sah (ujian struktur) · setiap sasaran `active` **unik dan kelihatan** dalam render
   halaman `route`-nya pada `viewport` yang dinyatakan, selepas `state` disediakan.
2. Tahan morph Livewire (atribut datang dari HTML server).
3. Yatim dua hala = 0: setiap `target` bukan-generik dalam katalog wujud dalam registry;
   setiap entri registry dirujuk ≥1 guide ATAU bertanda `reserved`.
4. Penamaan: `{skrin}-{fungsi}` (cth `records-search`, `approvals-approve`).
5. `generic-justified` hanya melalui allowlist bersebab + bertarikh (manifest baseline).
