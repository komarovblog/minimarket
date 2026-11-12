# Regression: one command reparses itself if git pull updates the release file.
# No Joomla install, network or real repository mutation.
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$releaseSource = Join-Path $PSScriptRoot 'release-to-desktop.ps1'
$source = Get-Content -LiteralPath $releaseSource -Raw -Encoding UTF8
$bootstrapEnd = '# Parse all release scripts before any build step.'
$cut = $source.IndexOf($bootstrapEnd, [StringComparison]::Ordinal)
if ($cut -lt 0) { throw 'MINIMARKET_SELF_UPDATE_TEST_BOOTSTRAP_NOT_FOUND' }
$prefix = $source.Substring(0, $cut)
$pullMarker = 'if (-not $SkipPull) {'
if (-not $prefix.Contains($pullMarker) -or
    -not $prefix.Contains('$releaseScriptHashBeforePull') -or
    -not $prefix.Contains('$releaseScriptHashAfterPull') -or
    -not $prefix.Contains('& $releaseScriptPath -SkipPull -DesktopPath $DesktopPath') -or
    -not $prefix.Contains("Write-Output 'MINIMARKET_RELEASE_SCRIPT_UPDATED:")) {
    throw 'MINIMARKET_SELF_UPDATE_TEST_GUARD_MISSING'
}

$originalLocation = Get-Location
$fixtureRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('mm-release-self-update-' + [guid]::NewGuid().ToString('N'))
$scriptsDir = Join-Path $fixtureRoot 'scripts'
$componentDir = Join-Path $fixtureRoot 'src\com_minimarket'
$selectedDesktop = Join-Path $fixtureRoot 'Desktop with spaces'
try {
    New-Item -ItemType Directory -Path $scriptsDir -Force | Out-Null
    New-Item -ItemType Directory -Path $componentDir -Force | Out-Null
    New-Item -ItemType Directory -Path $selectedDesktop -Force | Out-Null
    Set-Content -LiteralPath (Join-Path $componentDir 'minimarket.xml') -Value '<extension><version>0.6.4</version></extension>' -Encoding UTF8

    # Mock git replaces this executing file as real pull would.
    $fakeGit = @'
function git {
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'release-updated.ps1') -Destination (Join-Path $PSScriptRoot 'release-to-desktop.ps1') -Force
    $global:LASTEXITCODE = 0
}
'@
    $oldBody = $prefix.Replace($pullMarker, $fakeGit + [Environment]::NewLine + $pullMarker)
    if ($oldBody -eq $prefix) { throw 'MINIMARKET_SELF_UPDATE_TEST_MOCK_NOT_INSERTED' }
    $oldBody += [Environment]::NewLine + "Write-Output 'STALE_RELEASE_BODY_REACHED'" + [Environment]::NewLine
    $freshBody = @'
param(
    [string]$DesktopPath = '',
    [switch]$SkipPull
)
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
if (-not $SkipPull) { throw 'MINIMARKET_SELF_UPDATE_TEST_DOUBLE_PULL' }
Write-Output ('FRESH_RELEASE_BODY_EXECUTED:' + $DesktopPath)
'@
    $fixtureScript = Join-Path $scriptsDir 'release-to-desktop.ps1'
    Set-Content -LiteralPath $fixtureScript -Value $oldBody -Encoding UTF8
    Set-Content -LiteralPath (Join-Path $scriptsDir 'release-updated.ps1') -Value $freshBody -Encoding UTF8

    $lines = @(& $fixtureScript -DesktopPath $selectedDesktop)
    $output = $lines -join [Environment]::NewLine
    if (-not $output.Contains('MINIMARKET_RELEASE_SCRIPT_UPDATED:') -or
        -not $output.Contains('FRESH_RELEASE_BODY_EXECUTED:' + $selectedDesktop) -or
        $output.Contains('STALE_RELEASE_BODY_REACHED')) {
        throw ('MINIMARKET_SELF_UPDATE_TEST_RESTART_FAILED: ' + $output)
    }
    Write-Output 'MINIMARKET_RELEASE_SELF_UPDATE_WINDOWS_PASS'
}
finally {
    Set-Location -LiteralPath $originalLocation.Path
    if (Test-Path -LiteralPath $fixtureRoot) {
        Remove-Item -LiteralPath $fixtureRoot -Recurse -Force
    }
}
