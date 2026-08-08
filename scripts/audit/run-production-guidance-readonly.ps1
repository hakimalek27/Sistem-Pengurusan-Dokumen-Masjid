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
    [switch] $CleanupOnly
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

# ── Validasi env — NAMA sahaja dalam ralat, nilai TIDAK pernah dicetak (P18-03) ──────────
foreach ($name in @('E2E_PROD_SUPERADMIN_EMAIL', 'E2E_PROD_SUPERADMIN_PASSWORD')) {
    $value = [Environment]::GetEnvironmentVariable($name)
    if ([string]::IsNullOrWhiteSpace($value)) {
        throw "Env $name WAJIB dibekalkan pemanggil (pengurus kata laluan / sesi pentadbir). " +
              'Tanpa semakan ini, lalai diam guidance.spec.js akan menghantar kredensial demo ke produksi.'
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
        Tee-Object -FilePath $log -Append
    Get-ContainerFile "$serverSecret.before" | Set-Content (Join-Path $evidenceDir 'inventory-before.json')
    Remove-ContainerFile "$serverSecret.before"

    if (-not $CleanupOnly) {
        # PREPARE — kredensial role ditulis ke fail server sementara, ditarik ke fail lokal
        # ber-ACL ketat, kemudian DIPADAM di server. Superadmin tidak disentuh command.
        Invoke-ServerArtisan "diwan:audit-fixture prepare --run=$RunUuid --json=$serverSecret" |
            Tee-Object -FilePath $log -Append
        Get-ContainerFile $serverSecret | Set-Content $localSecret
        icacls $localSecret /inheritance:r /grant:r "$($env:USERNAME):(R)" | Out-Null

        $inventoryJson = Get-Content $localSecret -Raw | ConvertFrom-Json

        # 🔴 F8 (Codex P2 #13) — `cleanup --force` TANPA `--json` memadam ikut corak e-mel dan
        # slug, bukan ikut ID `created`. Itu bercanggah dengan kontrak §9.1a "padam hanya ID
        # yang dicipta larian ini". Tetapi fail kredensial penuh TIDAK boleh ditinggalkan dalam
        # kontena (#12). Penyelesaian: tulis inventori TEREDAKSI — `run_uuid` + `slug` +
        # `created` sahaja, TIADA kata laluan — dan padam fail penuh dari kontena sekarang.
        $redacted = @{
            run_uuid = $inventoryJson.run_uuid
            slug     = $inventoryJson.slug
            created  = $inventoryJson.created
        } | ConvertTo-Json -Depth 8
        Set-ContainerFile $serverInventory $redacted
        Remove-ContainerFile $serverSecret
        ($inventoryJson | Select-Object run_uuid, slug, created |
            ConvertTo-Json -Depth 6) | Set-Content (Join-Path $evidenceDir 'inventory-created.json')

        # Akaun role → env PROSES ANAK sahaja (bukan $env: global, bukan transcript).
        $roleAccounts = @($inventoryJson.role_credentials | ForEach-Object {
            @{ role = $_.role; email = $_.email; password = $_.password }
        }) | ConvertTo-Json -Compress

        $report = Join-Path $evidenceDir 'route-manifest.json'
        $psi = New-Object System.Diagnostics.ProcessStartInfo
        $psi.FileName = 'npx'
        # F8: `--project` WAJIB. Tanpanya Playwright menapis ikut project dan memberi
        # `Error: No tests found.` — disahkan empirikal. Lihat komen dalam playwright.config.js.
        # `--global-timeout` dikuatkuasakan oleh proses UTAMA Playwright, bukan oleh worker —
        # itu sebabnya ia berkesan apabila had per-ujian tidak, dan sebabnya ia diberi di sini
        # dan bukan hanya sebagai `timeout` dalam config.
        $globalTimeoutMs = $TimeoutMinutes * 60 * 1000
        $psi.Arguments = "playwright test --project=production-readonly --workers=1 --global-timeout $globalTimeoutMs"
        $psi.WorkingDirectory = $repoRoot
        $psi.UseShellExecute = $false
        $psi.EnvironmentVariables['E2E_PRODUCTION'] = '1'
        $psi.EnvironmentVariables['E2E_BASE_URL'] = $BaseUrl
        $psi.EnvironmentVariables['E2E_PROD_TENANT'] = $slug
        $psi.EnvironmentVariables['E2E_PROD_ROLE_ACCOUNTS'] = $roleAccounts
        $psi.EnvironmentVariables['E2E_PROD_SUPERADMIN_EMAIL'] = $env:E2E_PROD_SUPERADMIN_EMAIL
        $psi.EnvironmentVariables['E2E_PROD_SUPERADMIN_PASSWORD'] = $env:E2E_PROD_SUPERADMIN_PASSWORD
        $psi.EnvironmentVariables['E2E_PROD_REPORT'] = $report
        $psi.EnvironmentVariables['DIWAN_PW_JSON'] = (Join-Path $evidenceDir 'playwright-report.json')
        $process = [System.Diagnostics.Process]::Start($psi)
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
finally {
    # CLEANUP sentiasa berjalan (Ctrl-C/ralat/putus rangkaian larian seterusnya guna -CleanupOnly).
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
            Tee-Object -FilePath $log -Append
        Get-ContainerFile "$serverSecret.after" | Set-Content (Join-Path $evidenceDir 'inventory-after.json')
        Remove-ContainerFile "$serverSecret.after"
        Remove-ContainerFile $serverSecret
        Remove-ContainerFile $serverInventory
    } catch {
        "AMARAN cleanup: $($_.Exception.Message) — jalankan semula dengan -CleanupOnly -RunUuid $RunUuid" |
            Tee-Object -FilePath $log -Append
    }
    if (Test-Path $localSecret) { Remove-Item -Force $localSecret }

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

    "tamat=$(Get-Date -Format o)" | Tee-Object -FilePath $log -Append
}

Write-Host "Selesai. Bukti: $evidenceDir (run_uuid=$RunUuid)"
