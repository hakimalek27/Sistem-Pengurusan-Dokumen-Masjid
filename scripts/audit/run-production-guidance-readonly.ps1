# D11 #10 (PELAN-PEMBAIKAN.md §9.1a) — wrapper TUNGGAL larian matriks produksi read-only.
# Kontrak: run_uuid caller-provided ATAU dijana (SENTIASA direkod); fixture run-scoped
# (tenant smoke-<uuid>); kredensial role dlm fail sementara ber-ACL sahaja; superadmin
# DIBEKAL LUARAN (env pemanggil — tidak pernah ditulis ke cakera); cleanup try/finally
# idempotent; -CleanupOnly untuk pemulihan larian terputus.
#
# Guna:
#   pwsh -File scripts/audit/run-production-guidance-readonly.ps1 [-RunUuid <uuid>] `
#        [-BaseUrl https://bakwim.my] [-Server ubuntu@43.156.242.188] [-ComposeDir /opt/diwan] `
#        [-CleanupOnly]
# Env WAJIB pemanggil: E2E_PROD_SUPERADMIN_EMAIL, E2E_PROD_SUPERADMIN_PASSWORD.

[CmdletBinding()]
param(
    [string] $RunUuid,
    [string] $BaseUrl = 'https://bakwim.my',
    [string] $Server = 'ubuntu@43.156.242.188',
    [string] $ComposeDir = '/opt/diwan',
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

$serverSecret = "/tmp/diwan-audit-$RunUuid.json"
$localSecret = Join-Path $env:TEMP "diwan-audit-$RunUuid.json"

try {
    # Inventori BEFORE (read-only).
    Invoke-ServerArtisan "diwan:audit-fixture inventory --run=$RunUuid --json=$serverSecret.before" |
        Tee-Object -FilePath $log -Append
    ssh $Server "cat $serverSecret.before" | Set-Content (Join-Path $evidenceDir 'inventory-before.json')
    ssh $Server "rm -f $serverSecret.before"

    if (-not $CleanupOnly) {
        # PREPARE — kredensial role ditulis ke fail server sementara, ditarik ke fail lokal
        # ber-ACL ketat, kemudian DIPADAM di server. Superadmin tidak disentuh command.
        Invoke-ServerArtisan "diwan:audit-fixture prepare --run=$RunUuid --json=$serverSecret" |
            Tee-Object -FilePath $log -Append
        ssh $Server "cat $serverSecret" | Set-Content $localSecret
        ssh $Server "rm -f $serverSecret"
        icacls $localSecret /inheritance:r /grant:r "$($env:USERNAME):(R)" | Out-Null

        $inventoryJson = Get-Content $localSecret -Raw | ConvertFrom-Json
        ($inventoryJson | Select-Object run_uuid, slug, created |
            ConvertTo-Json -Depth 6) | Set-Content (Join-Path $evidenceDir 'inventory-created.json')

        # Akaun role → env PROSES ANAK sahaja (bukan $env: global, bukan transcript).
        $roleAccounts = @($inventoryJson.role_credentials | ForEach-Object {
            @{ role = $_.role; email = $_.email; password = $_.password }
        }) | ConvertTo-Json -Compress

        $report = Join-Path $evidenceDir 'route-manifest.json'
        $psi = New-Object System.Diagnostics.ProcessStartInfo
        $psi.FileName = 'npx'
        $psi.Arguments = 'playwright test e2e/production-guidance-readonly.spec.js --workers=1'
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
        $process.WaitForExit()
        "playwright exit=$($process.ExitCode)" | Tee-Object -FilePath $log -Append
        if ($process.ExitCode -ne 0) { throw "Spec produksi gagal (exit $($process.ExitCode)) — lihat $log" }

        node scripts/audit/assert-playwright-json.mjs --file (Join-Path $evidenceDir 'playwright-report.json') --min-tests 1
        if ($LASTEXITCODE -ne 0) { throw 'assert-playwright-json gagal untuk larian produksi.' }
    }
}
finally {
    # CLEANUP sentiasa berjalan (Ctrl-C/ralat/putus rangkaian larian seterusnya guna -CleanupOnly).
    try {
        Invoke-ServerArtisan "diwan:audit-fixture cleanup --run=$RunUuid --force" |
            Tee-Object -FilePath $log -Append
        Invoke-ServerArtisan "diwan:audit-fixture inventory --run=$RunUuid --json=$serverSecret.after" |
            Tee-Object -FilePath $log -Append
        ssh $Server "cat $serverSecret.after" | Set-Content (Join-Path $evidenceDir 'inventory-after.json')
        ssh $Server "rm -f $serverSecret.after $serverSecret"
    } catch {
        "AMARAN cleanup: $($_.Exception.Message) — jalankan semula dengan -CleanupOnly -RunUuid $RunUuid" |
            Tee-Object -FilePath $log -Append
    }
    if (Test-Path $localSecret) { Remove-Item -Force $localSecret }
    "tamat=$(Get-Date -Format o)" | Tee-Object -FilePath $log -Append
}

Write-Host "Selesai. Bukti: $evidenceDir (run_uuid=$RunUuid)"
