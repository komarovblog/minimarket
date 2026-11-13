param(
    [string]$DesktopPath = '',
    [switch]$SkipPull,
    [switch]$IncludeTaskPlugin
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

# Default: component ZIP and optional demo SQL only. Reservation accounting is
# inside the component, not in the separately installable optional scheduler.
# The one-time toolbar module remains installed independently; do not regenerate it.
$root = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path -LiteralPath (Join-Path $root 'src\com_minimarket\minimarket.xml') -PathType Leaf)) {
    throw "MINIMARKET_PROJECT_ROOT_NOT_FOUND: $root"
}
Set-Location -LiteralPath $root

# PowerShell parses a script before running it. A git pull may replace this
# very file, but the current invocation would still execute its old body.
# Record the bootstrap file hash so one command always uses current code.
$releaseScriptPath = $MyInvocation.MyCommand.Path
$releaseScriptHashBeforePull = (Get-FileHash -LiteralPath $releaseScriptPath -Algorithm SHA256).Hash

if (-not $SkipPull) {
    & git -C $root pull --ff-only origin development
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_GIT_PULL_FAILED' }

    $releaseScriptHashAfterPull = (Get-FileHash -LiteralPath $releaseScriptPath -Algorithm SHA256).Hash
    if ($releaseScriptHashAfterPull -ne $releaseScriptHashBeforePull) {
        Write-Output 'MINIMARKET_RELEASE_SCRIPT_UPDATED: restart current script after git pull'
        # New invocation re-parses the updated file. Skip a second pull; retain
        # the selected Desktop path. Return stops the previously parsed body.
        & $releaseScriptPath -SkipPull -DesktopPath $DesktopPath -IncludeTaskPlugin:$IncludeTaskPlugin
        return
    }
}

# Parse all release scripts before any build step. PowerShell 5.1's
# parser catches an unmatched bracket before it reaches a runtime check.
foreach ($scriptName in @('release-to-desktop.ps1', 'verify-admin-lists.ps1',
    'verify-0.5.87.ps1', 'verify-0.5.88.ps1', 'verify-0.5.89.ps1', 'verify-0.5.90.ps1',
    'verify-translations.ps1', 'test-release-self-update.ps1',
    'build.ps1', 'build-task-plugin.ps1', 'build-toolbar-package.ps1', 'inspect-package.ps1')) {
    $scriptPath = Join-Path $PSScriptRoot $scriptName
    $tokens = $null
    $parseErrors = $null
    [void][System.Management.Automation.Language.Parser]::ParseFile(
        $scriptPath, [ref]$tokens, [ref]$parseErrors)
    if ($parseErrors.Count -gt 0) {
        foreach ($issue in $parseErrors) {
            Write-Output ("MINIMARKET_POWERSHELL_SYNTAX_ERROR: {0}:{1}: {2}" -f
                $scriptName, $issue.Extent.StartLineNumber, $issue.Message)
        }
        throw "MINIMARKET_POWERSHELL_SYNTAX_FAILED: $scriptName"
    }
}
Write-Output 'MINIMARKET_POWERSHELL_SYNTAX_PASS'

# Avoid hardcoded OSPanel/PHP paths. Build and inspect within the current
# PowerShell process so Windows does not need to locate a second interpreter.
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'verify-admin-lists.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_ADMIN_LIST_CHECK_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'verify-0.5.87.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_087_VALIDATION_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'verify-0.5.88.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_088_VALIDATION_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'verify-0.5.89.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_089_VALIDATION_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'verify-0.5.90.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_090_NATIVE_LIST_VALIDATION_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'verify-translations.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_TRANSLATION_AUDIT_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'build.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_BUILD_FAILED' }
$global:LASTEXITCODE = 0
& (Join-Path $PSScriptRoot 'inspect-package.ps1')
if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_PACKAGE_INSPECTION_FAILED' }
$global:LASTEXITCODE = 0
if($IncludeTaskPlugin){
    & (Join-Path $PSScriptRoot 'build-task-plugin.ps1')
    if ($LASTEXITCODE -ne 0) { throw 'MINIMARKET_TASK_PLUGIN_BUILD_FAILED' }
}

[xml]$manifest = Get-Content -LiteralPath (Join-Path $root 'src\com_minimarket\minimarket.xml') -Raw -Encoding UTF8
$version = [string]$manifest.extension.version
if ($version -notmatch '^\d+\.\d+\.\d+$') {
    throw "MINIMARKET_INVALID_VERSION: $version"
}
if($IncludeTaskPlugin){
    [xml]$taskManifest=Get-Content -LiteralPath (Join-Path $root 'src\plg_task_minimarketreserves\minimarketreserves.xml') -Raw -Encoding UTF8
    $taskVersion=[string]$taskManifest.extension.version
    if ($taskVersion -notmatch '^\d+\.\d+\.\d+$') {
        throw "MINIMARKET_TASK_VERSION_INVALID: $taskVersion"
    }
}
$demoName = "minimarket_mysql84_nnop3_update_$($version.Replace('.', '_')).sql"
$demo = Join-Path (Join-Path $root 'sample-data') $demoName
if (-not (Test-Path -LiteralPath $demo -PathType Leaf)) {
    throw "MINIMARKET_DEMO_SQL_NOT_READY_FOR_VERSION: $version"
}
# Desktop may be redirected to OneDrive. Windows supplies the actual location.
if ([string]::IsNullOrWhiteSpace($DesktopPath)) {
    $DesktopPath = [Environment]::GetFolderPath('Desktop')
}
if ([string]::IsNullOrWhiteSpace($DesktopPath)) {
    $DesktopPath = Join-Path $env:USERPROFILE 'Desktop'
}
if (-not (Test-Path -LiteralPath $DesktopPath -PathType Container)) {
    throw "MINIMARKET_DESKTOP_NOT_FOUND: $DesktopPath"
}

$deliverables = @(
    @{ Source = Join-Path $root "dist\com_minimarket-$version.zip"; Name = "com_minimarket-$version.zip" },
    @{ Source = $demo; Name = $demoName }
)
if($IncludeTaskPlugin){
    $deliverables += @{ Source = Join-Path $root "dist\plg_task_minimarketreserves-$taskVersion.zip"; Name = "plg_task_minimarketreserves-$taskVersion.zip" }
}
foreach ($item in $deliverables) {
    if (-not (Test-Path -LiteralPath $item.Source -PathType Leaf)) {
        throw "MINIMARKET_REQUIRED_FILE_MISSING: $($item.Source)"
    }
}
foreach ($item in $deliverables) {
    $dst = Join-Path $DesktopPath $item.Name
    Copy-Item -LiteralPath $item.Source -Destination $dst -Force
    $fromHash = (Get-FileHash -LiteralPath $item.Source -Algorithm SHA256).Hash
    $toHash = (Get-FileHash -LiteralPath $dst -Algorithm SHA256).Hash
    if ($fromHash -ne $toHash) {
        throw "MINIMARKET_DESKTOP_HASH_MISMATCH: $dst"
    }
    Write-Output "DESKTOP_FILE_OK: $dst"
}
if($IncludeTaskPlugin){
    Write-Output "MINIMARKET_DESKTOP_3_FILES_OK: version=$version"
}else{
    Write-Output "MINIMARKET_DESKTOP_2_FILES_OK: version=$version"
    Write-Output 'SCHEDULER_OPTIONAL: reservation accounting works without plg_task_minimarketreserves.'
}
Write-Output 'SQL_IS_OPTIONAL: for the local demo catalog only; not required for Joomla UI changes. Back up before importing.'
) {
        throw "MINIMARKET_TASK_VERSION_INVALID: $taskVersion"
    }
}
$demoName = "minimarket_mysql84_nnop3_update_$($version.Replace('.', '_')).sql"
$demo = Join-Path (Join-Path $root 'sample-data') $demoName
if (-not (Test-Path -LiteralPath $demo -PathType Leaf)) {
    throw "MINIMARKET_DEMO_SQL_NOT_READY_FOR_VERSION: $version"
}
# Desktop may be redirected to OneDrive. Windows supplies the actual location.
if ([string]::IsNullOrWhiteSpace($DesktopPath)) {
    $DesktopPath = [Environment]::GetFolderPath('Desktop')
}
if ([string]::IsNullOrWhiteSpace($DesktopPath)) {
    $DesktopPath = Join-Path $env:USERPROFILE 'Desktop'
}
if (-not (Test-Path -LiteralPath $DesktopPath -PathType Container)) {
    throw "MINIMARKET_DESKTOP_NOT_FOUND: $DesktopPath"
}

$deliverables = @(
    @{ Source = Join-Path $root "dist\com_minimarket-$version.zip"; Name = "com_minimarket-$version.zip" },
    @{ Source = Join-Path $root "dist\plg_task_minimarketreserves-$taskVersion.zip"; Name = "plg_task_minimarketreserves-$taskVersion.zip" },
    @{ Source = $demo; Name = $demoName }
)
foreach ($item in $deliverables) {
    if (-not (Test-Path -LiteralPath $item.Source -PathType Leaf)) {
        throw "MINIMARKET_REQUIRED_FILE_MISSING: $($item.Source)"
    }
}
foreach ($item in $deliverables) {
    $dst = Join-Path $DesktopPath $item.Name
    Copy-Item -LiteralPath $item.Source -Destination $dst -Force
    $fromHash = (Get-FileHash -LiteralPath $item.Source -Algorithm SHA256).Hash
    $toHash = (Get-FileHash -LiteralPath $dst -Algorithm SHA256).Hash
    if ($fromHash -ne $toHash) {
        throw "MINIMARKET_DESKTOP_HASH_MISMATCH: $dst"
    }
    Write-Output "DESKTOP_FILE_OK: $dst"
}
Write-Output "MINIMARKET_DESKTOP_3_FILES_OK: version=$version"
Write-Output 'SQL_IS_OPTIONAL: for the local demo catalog only; not required for Joomla UI changes. Back up before importing.'
