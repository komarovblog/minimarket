# Creates only a local disposable index.php for testing Apache + PHP.
# Does NOT install Joomla or modify a previously installed site.
[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
$root = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$repoMarker = Join-Path $root '.git'
if (-not (Test-Path -LiteralPath $repoMarker)) {
    throw 'MINIMARKET_GIT_REPOSITORY_NOT_FOUND'
}

$branch = (& git -C $root branch --show-current 2>&1 | Out-String).Trim()
if ($LASTEXITCODE -ne 0 -or $branch -ne 'development') {
    throw ('MINIMARKET_EXPECTED_DEVELOPMENT_BRANCH; current=' + $branch)
}

$origin = (& git -C $root remote get-url origin 2>&1 | Out-String).Trim()
if ($LASTEXITCODE -ne 0 -or
    ($origin -notmatch '(?i)(?:^|[:/])komarovblog/minimarket(?:\.git)?$')) {
    throw 'MINIMARKET_UNEXPECTED_GITHUB_ORIGIN'
}

$profile = Join-Path $root '.osp\project.ini'
if (-not (Test-Path -LiteralPath $profile -PathType Leaf)) {
    throw 'MINIMARKET_OSP_PROJECT_CONFIG_MISSING'
}

$public = Join-Path $root 'public'
if (-not (Test-Path -LiteralPath $public -PathType Container)) {
    [void](New-Item -ItemType Directory -Path $public)
}

$index = Join-Path $public 'index.php'
if (Test-Path -LiteralPath $index) {
    Write-Output 'INDEX_ALREADY_EXISTS_NOT_MODIFIED'
    exit 0
}

$placeholder = @'
<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=UTF-8');
header('X-Robots-Tag: noindex');
header('Cache-Control: no-store');
echo "MINIMARKET_BOOTSTRAP_OK\n";
echo "PHP_VERSION=" . PHP_VERSION . "\n";
echo "JOOMLA_NOT_INSTALLED\n";
'@

[System.IO.File]::WriteAllText(
    $index,
    $placeholder,
    (New-Object System.Text.UTF8Encoding($false))
)
Write-Output 'LOCAL_MINIMARKET_INDEX_CREATED'
Write-Output 'NEXT_RESTART_OSP_AND_TEST_HTTP'
