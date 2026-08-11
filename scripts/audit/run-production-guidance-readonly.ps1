# D11 #10 (PELAN-PEMBAIKAN.md §9.1a) — wrapper TUNGGAL larian matriks produksi read-only.
# Kontrak: run_uuid caller-provided ATAU dijana (SENTIASA direkod); fixture run-scoped
# (tenant smoke-<uuid>); kredensial role dlm fail sementara ber-ACL sahaja; superadmin
# DIBEKAL LUARAN (env pemanggil — tidak pernah ditulis ke cakera); cleanup try/finally
# idempotent; -CleanupOnly untuk pemulihan larian terputus.
#
# Guna:
#   pwsh -File scripts/audit/run-production-guidance-readonly.ps1 [-RunUuid <uuid>] `
#        [-BaseUrl https://bakwim.my] [-Server ubuntu@43.156.242.188] [-ComposeDir /opt/diwan] `
#        [-TimeoutMinutes 120] [-CleanupOnly]
# Had 120 minit memberi ~3× ruang: 20 log masuk × jarak 15s = 300s, ~500 muatan halaman merentas
# internet ≈ 30–45 minit. Naikkan jika rangkaian perlahan; JANGAN buang — larian tanpa had ialah
# cara tetingkap kredensial terbakar tanpa hasil.
# Env WAJIB pemanggil: E2E_PROD_SUPERADMIN_EMAIL, E2E_PROD_SUPERADMIN_PASSWORD.

[CmdletBinding()]
param(
    [string] $RunUuid,
    [string] $BaseUrl = 'https://bakwim.my',
    [string] $Server = 'ubuntu@43.156.242.188',
    [string] $ComposeDir = '/opt/diwan',
    # F8 — had MENYELURUH. Latihan tempatan 9 Ogos membuktikan had per-ujian TIDAK MENCUKUPI:
    # satu konteks terkunci melepasi hadnya sendiri (600s) tanpa gagal, kerana had per-ujian
    # dikuatkuasakan DI DALAM worker yang terkunci itu. `WaitForExit()` tanpa argumen kemudian
    # menunggu selama-lamanya — iaitu tepat cara tetingkap kredensial pemilik akan terbakar.
    [int] $TimeoutMinutes = 120,
    [switch] $CleanupOnly,

    # ── Mod KETULAN (F8, 11 Ogos 2026) ───────────────────────────────────────────────────────
    # Larian tunggal 20 konteks mengambil ~26 minit dan **dibunuh dari luar** pada mesin dev ini
    # (diukur: mati pada minit ~21, 7/20 konteks siap, cleanup tidak berjalan → fixture tersasar
    # pada produksi). Latihan tempatan 9 Ogos menyelesaikan matriks 20/20 hanya selepas ia
    # dipecahkan kepada ketulan. Ketiga-tiga suis di bawah membenarkan corak itu **tanpa**
    # mencipta fixture berulang kali: satu `prepare`, beberapa larian, satu `cleanup`.
    #   -Grep               tapis konteks (dihantar sebagai --grep kepada Playwright)
    #   -KeepFixture        JANGAN cleanup di hujung (ketulan bukan yang terakhir)
    #   -UseExistingFixture JANGAN prepare; guna rahsia tempatan run_uuid yang sama
    # ⚠️ Keadaan per-konteks spec disandarkan pada CAKERA dan dikunci pada `run_tenant`, jadi
    #    ketulan HANYA terkumpul jika run_uuid (dan dengan itu slug tenant) kekal sama.
    [string] $Grep,
    [switch] $KeepFixture,
    [switch] $UseExistingFixture,

    # Diagnostik: `DEBUG=pw:api` menyebabkan Playwright mencatat SETIAP panggilan API yang
    # dimulakan dan diselesaikan. Empat konteks tergantung pada `/delegasi` tanpa SATU pun had
    # menembak (navigasi 60s, expect 30s, evaluate 45s, ujian 600s) — jadi panggilan mana yang
    # tergantung mesti diperhatikan, bukan disimpulkan. Bonus: log berterusan bermakna tugas
    # tidak lagi SENYAP, dan kesenyapan itulah yang menyebabkan larian dibunuh dari luar.
    [switch] $PwDebug
)

$ErrorActionPreference = 'Stop'
$repoRoot = Resolve-Path (Join-Path $PSScriptRoot '..' '..')
Set-Location $repoRoot

# ── run_uuid: diberi → guna apa adanya (validasi, TIADA pembetulan senyap); tiada → jana ──
$uuidPattern = '^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'
if ($CleanupOnly -and -not $RunUuid) { throw '-CleanupOnly memerlukan -RunUuid (tiada tekaan).' }
if ($RunUuid) {
    if ($RunUuid -notmatch $uuidPattern) { throw "-RunUuid bukan UUIDv4 sah: $RunUuid" }
} else {
    $RunUuid = [guid]::NewGuid().ToString()
}
$slug = "smoke-$RunUuid"
$evidenceDir = Join-Path $repoRoot "Audit Review Round Robin/bukti/plan-f8/$RunUuid"
New-Item -ItemType Directory -Force $evidenceDir | Out-Null
$log = Join-Path $evidenceDir 'run.log'
"run_uuid=$RunUuid  slug=$slug  base_url=$BaseUrl  mula=$(Get-Date -Format o)" | Tee-Object -FilePath $log

# ── Sumber kredensial: fail TEMPATAN dahulu, kemudian env ─────────────────────────────────
# Mengapa fail dan bukan hanya env: env yang ditetapkan dalam satu shell TIDAK bertahan kepada
# proses lain, dan menaipnya melalui pembantu bermakna nilainya muncul dalam transkrip
# perbualan — tepat perkara yang kita elak. Fail ini dibuat pemilik dalam editor sendiri,
# diabaikan git, dan HANYA dibaca oleh skrip ini. Nilainya tidak pernah dicetak.
$credFile = Join-Path $repoRoot '.e2e-prod-credentials.local.json'
if (Test-Path $credFile) {
    try {
        $cred = Get-Content -Raw -LiteralPath $credFile | ConvertFrom-Json
    } catch {
        throw "$credFile bukan JSON sah — jangkakan {""email"":""…"",""password"":""…""}"
    }
    if ($cred.email) { $env:E2E_PROD_SUPERADMIN_EMAIL = [string] $cred.email }
    if ($cred.password) { $env:E2E_PROD_SUPERADMIN_PASSWORD = [string] $cred.password }
    "kredensial superadmin dibaca daripada $([IO.Path]::GetFileName($credFile)) (nilai tidak dicetak)" |
        Tee-Object -FilePath $log -Append
}

# ── Validasi env — NAMA sahaja dalam ralat, nilai TIDAK pernah dicetak (P18-03) ──────────
foreach ($name in @('E2E_PROD_SUPERADMIN_EMAIL', 'E2E_PROD_SUPERADMIN_PASSWORD')) {
    $value = [Environment]::GetEnvironmentVariable($name)
    if ([string]::IsNullOrWhiteSpace($value)) {
        throw "Env $name WAJIB dibekalkan pemanggil, ATAU letakkan dalam $credFile " +
              '(JSON: {"email":"…","password":"…"} — diabaikan git). ' +
              'Tanpa semakan ini, lalai diam guidance.spec.js akan menghantar kredensial demo ke produksi.'
    }
}

# ── Runner: laluan SEBENAR npx + pra-terbang WAJIB sebelum menyentuh produksi ─────────────
# 🔴 F8 (larian 11 Ogos 23:13) — `$psi.FileName = 'npx'` dengan `UseShellExecute = $false`
# TIDAK BOLEH dilancarkan pada Windows: `npx` ialah `npx.cmd`, dan CreateProcess tidak
# menyelesaikan PATHEXT. Akibatnya wrapper mencipta tenant + 8 akaun pada PRODUKSI, kemudian
# mati serta-merta sebelum satu ujian pun berjalan — dan kerana dua pepijat lain di bawah,
# cleanup gagal juga, jadi produksi ditinggalkan dengan fixture tersasar.
$npxCommand = Get-Command npx -ErrorAction SilentlyContinue
if (-not $npxCommand) {
    throw 'npx tidak ditemui dalam PATH — Playwright tidak boleh dilancarkan. Berhenti SEBELUM menyentuh produksi.'
}
$npxPath = $npxCommand.Source
"npx = $npxPath" | Tee-Object -FilePath $log -Append

# Kedua-dua pra-terbang dan larian sebenar melalui fungsi ini, supaya pra-terbang
# mengesahkan mekanisme pelancaran yang SAMA — bukan yang serupa.
function Start-Playwright {
    param([string] $Arguments, [hashtable] $Environment, [switch] $Capture)

    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $npxPath
    $psi.Arguments = $Arguments
    $psi.WorkingDirectory = $repoRoot
    $psi.UseShellExecute = $false
    if ($Capture) {
        $psi.RedirectStandardOutput = $true
        $psi.RedirectStandardError = $true
    }
    foreach ($key in $Environment.Keys) {
        $psi.EnvironmentVariables[$key] = [string] $Environment[$key]
    }

    try {
        return [System.Diagnostics.Process]::Start($psi)
    } catch {
        # Mesej .NET ("The system cannot find the file specified") tidak menyebut PATHEXT
        # mahupun `.cmd`, jadi ia menghabiskan masa orang. Namakan puncanya.
        throw "Tidak dapat melancarkan runner '$($psi.FileName)': $($_.Exception.Message) " +
        '(pada Windows `npx` ialah `npx.cmd`; CreateProcess tidak menyelesaikan PATHEXT).'
    }
}

# PRA-TERBANG: buktikan runner boleh dilancarkan DAN spec boleh dikutip, SEBELUM apa-apa
# dicipta pada produksi. Ini menangkap kedua-dua kegagalan runner yang sudah berlaku
# ("No tests found" kerana `--project` hilang, dan `npx` tidak boleh dilancarkan) pada titik
# yang tidak meninggalkan kesan pada produksi. Akaun sintetik di bawah hanya untuk pengutipan
# — `--list` tidak menjalankan ujian, tidak membuka pelayar, tidak menyentuh rangkaian.
if (-not $CleanupOnly) {
    $preflightRoles = @('admin_masjid', 'pengerusi', 'setiausaha', 'bendahari', 'nazir', 'ketua_imam', 'ajk', 'audit')
    $preflightEnv = @{
        E2E_PRODUCTION               = '1'
        E2E_BASE_URL                 = $BaseUrl
        E2E_PROD_TENANT              = $slug
        E2E_PROD_ROLE_ACCOUNTS       = (@($preflightRoles | ForEach-Object {
                @{ role = $_; email = "$_-praterbang@invalid.test"; password = 'pra-terbang-tidak-pernah-dihantar' }
            }) | ConvertTo-Json -Compress)
        E2E_PROD_SUPERADMIN_EMAIL    = $env:E2E_PROD_SUPERADMIN_EMAIL
        E2E_PROD_SUPERADMIN_PASSWORD = $env:E2E_PROD_SUPERADMIN_PASSWORD
        E2E_PROD_REPORT              = (Join-Path $evidenceDir 'preflight-list.json')
    }
    $listProcess = Start-Playwright -Capture -Environment $preflightEnv `
        -Arguments 'playwright test --project=production-readonly --list'
    $listOut = $listProcess.StandardOutput.ReadToEnd()
    $listErr = $listProcess.StandardError.ReadToEnd()
    if (-not $listProcess.WaitForExit(180000)) {
        try { $listProcess.Kill($true) } catch { }
        throw 'Pra-terbang `--list` menggantung melebihi 3 minit — berhenti sebelum menyentuh produksi.'
    }
    if ($listProcess.ExitCode -ne 0) {
        throw "Pra-terbang runner GAGAL (exit $($listProcess.ExitCode)) — TIADA apa dicipta pada produksi.`n$listErr`n$listOut"
    }
    # Anti-vakum: `--list` yang mengutip SIFAR ujian keluar 0 pada sesetengah versi, jadi
    # kiraan mesti diassert dan bukan hanya kod keluar. 22 = 2 kontrak + 20 konteks.
    $dikutip = @([regex]::Matches($listOut, 'production-guidance-readonly\.spec\.js')).Count
    "pra-terbang: $dikutip ujian dikutip (mesti >= 22)" | Tee-Object -FilePath $log -Append
    if ($dikutip -lt 22) {
        throw "Pra-terbang mengutip $dikutip ujian, dijangka >= 22 (2 kontrak + 20 konteks). " +
        'Berhenti SEBELUM menyentuh produksi.'
    }
}

# 🔴 F8 (12 Ogos) — `diwan:audit-fixture inventory` memaparkan `superadmin.emails`, iaitu alamat
# e-mel PERIBADI pemilik, dan artifak bukti larian ini DIJEJAK oleh git. Imbasan sebelum commit
# pertama menemuinya dalam 8 fail. Diredaksi pada titik penulisan, bukan dibersihkan kemudian.
# Akaun fixture (@smoke.test) sengaja DIKEKALKAN — ia bukan rahsia dan ia bukti yang berguna.
function Redaksi {
    param([Parameter(ValueFromPipeline = $true)] [AllowNull()] [string] $Teks)

    process {
        if ([string]::IsNullOrEmpty($Teks)) { return $Teks }

        return [regex]::Replace(
            $Teks,
            '[A-Za-z0-9._%+-]+@(?!smoke\.test)[A-Za-z0-9.-]+\.[A-Za-z]{2,}',
            '<emel-diredaksi>')
    }
}

function Invoke-ServerArtisan {
    param([string] $ArtisanArgs)
    $cmd = "cd $ComposeDir && docker compose exec -T app php artisan $ArtisanArgs"
    ssh $Server $cmd
    if ($LASTEXITCODE -ne 0) { throw "SSH artisan gagal (exit $LASTEXITCODE): $ArtisanArgs" }
}

# 🔴 F8 (Codex P2 #12) — artisan berjalan DALAM kontena `app`, jadi `--json=/tmp/...` menulis ke
# `/tmp` KONTENA. Versi terdahulu membaca dan memadamnya dengan `ssh $Server "cat /tmp/..."`,
# iaitu `/tmp` HOS — fail itu tidak pernah ada di sana. Dua akibat: fail inventori yang
# "disalin" sebenarnya KOSONG, dan fail kredensial KEKAL hidup di dalam kontena.
# Kedua-dua operasi kini berlaku di tempat fail itu benar-benar berada.
function Get-ContainerFile {
    param([string] $Path)
    $out = ssh $Server "cd $ComposeDir && docker compose exec -T app sh -lc 'cat $Path'"
    if ($LASTEXITCODE -ne 0) { throw "Gagal membaca fail kontena: $Path" }
    return $out
}

function Remove-ContainerFile {
    param([string] $Path)
    ssh $Server "cd $ComposeDir && docker compose exec -T app sh -lc 'rm -f $Path'" | Out-Null
}

function Set-ContainerFile {
    param([string] $Path, [string] $Content)
    $b64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($Content))
    ssh $Server "cd $ComposeDir && docker compose exec -T app sh -lc 'echo $b64 | base64 -d > $Path'" | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "Gagal menulis fail kontena: $Path" }
}

$serverSecret = "/tmp/diwan-audit-$RunUuid.json"
# Inventori TEREDAKSI (ID sahaja, TIADA kata laluan) dikekalkan dalam kontena supaya
# `cleanup` boleh memadam ikut ID — lihat komen #13 di bawah.
$serverInventory = "/tmp/diwan-audit-$RunUuid.inventory.json"
$localSecret = Join-Path $env:TEMP "diwan-audit-$RunUuid.json"

try {
    # Inventori BEFORE (read-only).
    Invoke-ServerArtisan "diwan:audit-fixture inventory --run=$RunUuid --json=$serverSecret.before" |
        Redaksi | Tee-Object -FilePath $log -Append
    Get-ContainerFile "$serverSecret.before" | Redaksi | Set-Content (Join-Path $evidenceDir 'inventory-before.json')
    Remove-ContainerFile "$serverSecret.before"

    if (-not $CleanupOnly) {
        if ($UseExistingFixture) {
            # Ketulan lanjutan: fixture larian ini sudah ada pada produksi, dan kredensialnya
            # ada dalam fail tempatan ber-ACL. TIADA `prepare` — mencipta semula bermakna slug
            # tenant baharu, dan keadaan per-konteks pada cakera dikunci pada `run_tenant`,
            # jadi ketulan terdahulu akan hilang.
            if (-not (Test-Path $localSecret)) {
                throw "-UseExistingFixture diberi tetapi rahsia tempatan tiada: $localSecret. " +
                'Fixture untuk run_uuid ini tidak lagi tersedia — mulakan run_uuid BAHARU.'
            }
            "guna fixture sedia ada (tiada prepare) — rahsia: $([IO.Path]::GetFileName($localSecret))" |
                Tee-Object -FilePath $log -Append
        } else {
            # PREPARE — kredensial role ditulis ke fail server sementara, ditarik ke fail lokal
            # ber-ACL ketat, kemudian DIPADAM di server. Superadmin tidak disentuh command.
            Invoke-ServerArtisan "diwan:audit-fixture prepare --run=$RunUuid --json=$serverSecret" |
                Tee-Object -FilePath $log -Append
            Get-ContainerFile $serverSecret | Set-Content $localSecret
        }
        # 🔴 F8 (larian 11 Ogos) — `"$($env:USERNAME):(R)"` menghasilkan ACE cacat (`icacls`
        # memaparkannya sebagai `HAKIM\:(R)`) DAN hanya-baca. Kesannya: skrip ini tidak dapat
        # memadam fail rahsianya sendiri di hujung (`Access denied`), jadi kata laluan LAPAN
        # akaun fixture kekal di cakera selepas larian tamat. Prinsipal berkelayakan + (F);
        # akses tetap terhad kepada pengguna ini kerana pewarisan dibuang.
        icacls $localSecret /inheritance:r /grant:r "$($env:USERDOMAIN)\$($env:USERNAME):(F)" | Out-Null

        $inventoryJson = Get-Content $localSecret -Raw | ConvertFrom-Json

        # 🔴 F8 (Codex P2 #13) — `cleanup --force` TANPA `--json` memadam ikut corak e-mel dan
        # slug, bukan ikut ID `created`. Itu bercanggah dengan kontrak §9.1a "padam hanya ID
        # yang dicipta larian ini". Tetapi fail kredensial penuh TIDAK boleh ditinggalkan dalam
        # kontena (#12). Penyelesaian: tulis inventori TEREDAKSI — `run_uuid` + `slug` +
        # `created` sahaja, TIADA kata laluan — dan padam fail penuh dari kontena sekarang.
        if (-not $UseExistingFixture) {
            $redacted = @{
                run_uuid = $inventoryJson.run_uuid
                slug     = $inventoryJson.slug
                created  = $inventoryJson.created
            } | ConvertTo-Json -Depth 8
            Set-ContainerFile $serverInventory $redacted
            Remove-ContainerFile $serverSecret
            ($inventoryJson | Select-Object run_uuid, slug, created |
                ConvertTo-Json -Depth 6) | Set-Content (Join-Path $evidenceDir 'inventory-created.json')
        }

        # Akaun role → env PROSES ANAK sahaja (bukan $env: global, bukan transcript).
        $roleAccounts = @($inventoryJson.role_credentials | ForEach-Object {
            @{ role = $_.role; email = $_.email; password = $_.password }
        }) | ConvertTo-Json -Compress

        $report = Join-Path $evidenceDir 'route-manifest.json'
        # F8: `--project` WAJIB. Tanpanya Playwright menapis ikut project dan memberi
        # `Error: No tests found.` — disahkan empirikal. Lihat komen dalam playwright.config.js.
        # `--global-timeout` dikuatkuasakan oleh proses UTAMA Playwright, bukan oleh worker —
        # itu sebabnya ia berkesan apabila had per-ujian tidak, dan sebabnya ia diberi di sini
        # dan bukan hanya sebagai `timeout` dalam config.
        $globalTimeoutMs = $TimeoutMinutes * 60 * 1000
        # Mod ketulan: `--grep` menapis ikut TAJUK ujian ("desktop · nazir", "kontrak: …").
        # Ketulan yang tidak mengandungi ujian kontrak sengaja mengecualikannya — kontrak
        # "TEPAT 20 konteks" hanya boleh lulus selepas ketulan TERAKHIR, kerana ia membaca
        # keadaan pada cakera dan bukan keputusan larian ini.
        $grepArg = if ($Grep) { " --grep `"$Grep`"" } else { '' }
        if ($Grep) { "ketulan: --grep `"$Grep`"" | Tee-Object -FilePath $log -Append }
        $larianEnv = @{
            E2E_PRODUCTION               = '1'
            E2E_BASE_URL                 = $BaseUrl
            E2E_PROD_TENANT              = $slug
            E2E_PROD_ROLE_ACCOUNTS       = $roleAccounts
            E2E_PROD_SUPERADMIN_EMAIL    = $env:E2E_PROD_SUPERADMIN_EMAIL
            E2E_PROD_SUPERADMIN_PASSWORD = $env:E2E_PROD_SUPERADMIN_PASSWORD
            E2E_PROD_REPORT              = $report
            DIWAN_PW_JSON                = (Join-Path $evidenceDir 'playwright-report.json')
        }
        if ($PwDebug) {
            # ⚠️ `DEBUG=pw:api` ke STDOUT membunuh larian dengan cara yang bertentangan dengan
            # gantung: 948 baris dalam 2.5 minit dan tugas dihentikan kerana BANJIR output
            # (gantung pula dihentikan kerana KESENYAPAN). `DEBUG_FILE` mengalihkannya ke fail,
            # jadi stdout kekal nipis DAN diagnostik terselamat walaupun tugas dibunuh —
            # itulah yang penting: log itu menamakan panggilan terakhir sebelum gantung.
            $larianEnv['DEBUG'] = 'pw:api'
            $larianEnv['DEBUG_FILE'] = (Join-Path $evidenceDir 'pw-api.log')
            "DIAGNOSTIK: DEBUG=pw:api → $(Join-Path $evidenceDir 'pw-api.log')" | Tee-Object -FilePath $log -Append
        }
        $process = Start-Playwright -Environment $larianEnv `
            -Arguments "playwright test --project=production-readonly --workers=1 --global-timeout $globalTimeoutMs$grepArg"
        # Sandaran keras: beri Playwright 2 minit melebihi had menyeluruhnya untuk menutup
        # dirinya dengan kemas, kemudian bunuh. Tanpa ini, proses yang tidak menghormati
        # --global-timeout (worker terkunci) menggantung wrapper tanpa had.
        if (-not $process.WaitForExit($globalTimeoutMs + 120000)) {
            "TIMEOUT: playwright melepasi $TimeoutMinutes minit + 2 minit anjal — dibunuh." | Tee-Object -FilePath $log -Append
            try { $process.Kill($true) } catch { }
            $process.WaitForExit(30000) | Out-Null
        }
        "playwright exit=$($process.ExitCode)" | Tee-Object -FilePath $log -Append
        if ($process.ExitCode -ne 0) {
            # Laporan inventori ditulis BERPERINGKAT oleh spec, jadi ia wujud walaupun larian
            # terputus — arahkan operator kepadanya dan bukan hanya kepada log.
            throw "Spec produksi gagal (exit $($process.ExitCode)) — lihat $log dan inventori separa $report (medan 'missing_contexts' menamakan konteks yang tidak selesai)."
        }

        node scripts/audit/assert-playwright-json.mjs --file (Join-Path $evidenceDir 'playwright-report.json') --min-tests 1
        if ($LASTEXITCODE -ne 0) { throw 'assert-playwright-json gagal untuk larian produksi.' }
    }
}
catch {
    # 🔴 F8 (larian 11 Ogos) — pengecualian asal MESTI direkod SEBELUM `finally` berjalan.
    # Ralat dalam `finally` (cleanup, atau memadam fail rahsia) menggantikan pengecualian
    # yang sedang merambat, dan puncanya hilang sepenuhnya: kegagalan `npx` yang membatalkan
    # larian itu TIDAK PERNAH kelihatan — yang dilaporkan hanyalah `Access denied` pada
    # fail sementara. Log dahulu, baru lempar semula.
    "RALAT ASAL: $($_.Exception.Message)" | Tee-Object -FilePath $log -Append
    "  di: $($_.InvocationInfo.PositionMessage)" | Tee-Object -FilePath $log -Append
    throw
}
finally {
    # 🔴 F8 (larian 11 Ogos) — fail rahsia KONTENA dipadam DAHULU dan secara BERASINGAN.
    # Sebelum ini pemadamannya berada di hujung blok `try` yang sama seperti `cleanup`, jadi
    # apabila `cleanup` melempar (ia melempar: "Fail inventori tiada"), baris itu DILANGKAU —
    # dan fail yang mengandungi kata laluan LAPAN akaun fixture kekal hidup dalam `/tmp`
    # kontena PRODUKSI. Disahkan: `ls -la /tmp/diwan-audit-*` menunjukkannya 8 minit kemudian.
    if (-not $CleanupOnly) {
        try {
            Remove-ContainerFile $serverSecret
        } catch {
            "AMARAN: rahsia kontena $serverSecret TIDAK dapat dipadam — padam manual." |
                Tee-Object -FilePath $log -Append
        }
    }

    # Mod ketulan: fixture MESTI hidup untuk ketulan berikutnya. Amaran ini sengaja kuat —
    # fixture yang ditinggalkan pada produksi tanpa sesiapa perasan ialah kegagalan 11 Ogos.
    # ⚠️ Dilaksanakan sebagai SYARAT, bukan `return`: `return` di dalam `finally` PowerShell
    # membuang pengecualian yang sedang merambat — iaitu kelas pepijat yang baru dibaiki di atas.
    $simpanFixture = $KeepFixture -and -not $CleanupOnly
    if ($simpanFixture) {
        @(
            "KEEP-FIXTURE: tenant $slug DIBIARKAN HIDUP pada produksi untuk ketulan berikutnya.",
            "  Ketulan seterusnya : -RunUuid $RunUuid -UseExistingFixture [-KeepFixture] -Grep '<corak>'",
            "  WAJIB di hujung    : -CleanupOnly -RunUuid $RunUuid"
        ) | Tee-Object -FilePath $log -Append
    }

    # CLEANUP sentiasa berjalan (Ctrl-C/ralat/putus rangkaian larian seterusnya guna -CleanupOnly).
    if (-not $simpanFixture) {
    try {
        # Padam ikut ID inventori. `--force` HANYA dalam mod -CleanupOnly (pemulihan), di mana
        # tiada inventori tersedia dan padanan run-uuid ialah satu-satunya jalan.
        $cleanupArgs = if ($CleanupOnly) {
            "diwan:audit-fixture cleanup --run=$RunUuid --force"
        } else {
            "diwan:audit-fixture cleanup --run=$RunUuid --json=$serverInventory"
        }
        Invoke-ServerArtisan $cleanupArgs | Tee-Object -FilePath $log -Append
        Invoke-ServerArtisan "diwan:audit-fixture inventory --run=$RunUuid --json=$serverSecret.after" |
            Redaksi | Tee-Object -FilePath $log -Append
        Get-ContainerFile "$serverSecret.after" | Redaksi | Set-Content (Join-Path $evidenceDir 'inventory-after.json')
        Remove-ContainerFile "$serverSecret.after"
        Remove-ContainerFile $serverSecret
        Remove-ContainerFile $serverInventory
    } catch {
        "AMARAN cleanup: $($_.Exception.Message) — jalankan semula dengan -CleanupOnly -RunUuid $RunUuid" |
            Tee-Object -FilePath $log -Append
    }
    # 🔴 F8 (larian 11 Ogos) — `Remove-Item` di sini pernah MELEMPAR (ACL hanya-baca di atas),
    # dan kerana ia berada dalam `finally` SEBELUM semakan delta, kegagalannya (a) menutupi
    # punca larian dan (b) MELANGKAU assertion cleanup di bawah. Kedua-duanya tidak boleh
    # berlaku: rahsia tetap dipadam sebaik mungkin, tetapi keputusan larian menang.
    if (Test-Path $localSecret) {
        try {
            Remove-Item -Force $localSecret -ErrorAction Stop
        } catch {
            icacls $localSecret /grant "$($env:USERDOMAIN)\$($env:USERNAME):(F)" | Out-Null
            try {
                Remove-Item -Force $localSecret -ErrorAction Stop
            } catch {
                "AMARAN: fail kredensial sementara TIDAK dapat dipadam — padam manual: $localSecret" |
                    Tee-Object -FilePath $log -Append
            }
        }
    }

    # 🔴 F8 (Codex P2 #13) — sebelum ini `before`/`after` hanya DITULIS, deltanya tidak pernah
    # diassert. Inventori yang menunjukkan baki tenant/akaun larian ini kini RALAT, bukan nota.
    $afterPath = Join-Path $evidenceDir 'inventory-after.json'
    if (Test-Path $afterPath) {
        try {
            # ⚠️ Bentuk JSON DIUKUR pada keluaran `diwan:audit-fixture inventory` yang sebenar:
            #     { run_uuid, slug, counts, run_scoped: { mosque_exists, run_users }, superadmin }
            # Versi pertama semakan ini meneka `after.mosques.slugs` / `after.users.emails` —
            # medan yang TIDAK WUJUD, jadi ia sentiasa memberi 0 dan lulus secara VAKUM.
            $after = Get-Content $afterPath -Raw | ConvertFrom-Json
            $bakiMosque = [bool] $after.run_scoped.mosque_exists
            $bakiUser = [int] $after.run_scoped.run_users
            "delta cleanup: mosque_exists=$bakiMosque run_users=$bakiUser" | Tee-Object -FilePath $log -Append
            if ($null -eq $after.run_scoped) {
                throw "Inventori AFTER tiada blok `run_scoped` — bentuk berubah, semakan delta tidak sah."
            }
            if ($bakiMosque -or $bakiUser -gt 0) {
                throw "CLEANUP TIDAK LENGKAP: tenant_ada=$bakiMosque akaun_baki=$bakiUser bagi larian $RunUuid. " +
                      "Jalankan: -CleanupOnly -RunUuid $RunUuid"
            }
        } catch {
            "AMARAN delta: $($_.Exception.Message)" | Tee-Object -FilePath $log -Append
            throw
        }
    }
    } # tamat: if (-not $simpanFixture)

    "tamat=$(Get-Date -Format o)" | Tee-Object -FilePath $log -Append
}

Write-Host "Selesai. Bukti: $evidenceDir (run_uuid=$RunUuid)"
