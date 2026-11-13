param(
    [string]$OutputPath = ''
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
# Separate Joomla Scheduler plugin; never bundle it into com_minimarket.
$root=Split-Path -Parent $PSScriptRoot
$source=Join-Path $root 'src\plg_task_minimarketreserves'
[xml]$component=Get-Content -LiteralPath (Join-Path $root 'src\com_minimarket\minimarket.xml') -Raw -Encoding UTF8
[xml]$plugin=Get-Content -LiteralPath (Join-Path $source 'minimarketreserves.xml') -Raw -Encoding UTF8
$componentVersion=[string]$component.extension.version
$version=[string]$plugin.extension.version
if($version -notmatch '^\d+\.\d+\.\d+$' -or $componentVersion -notmatch '^\d+\.\d+\.\d+$'){
    throw "MINIMARKET_TASK_VERSION_INVALID: component=$componentVersion task=$version"
}
# Separate task plugin keeps its own version during component-only updates.
if([string]$plugin.extension.type -ne 'plugin' -or [string]$plugin.extension.group -ne 'task'){
    throw 'MINIMARKET_TASK_MANIFEST_INVALID'
}
$required=@(
    'minimarketreserves.xml',
    'services\provider.php',
    'src\Extension\Minimarketreserves.php',
    'language\ru-RU\plg_task_minimarketreserves.ini',
    'language\ru-RU\plg_task_minimarketreserves.sys.ini',
    'language\en-GB\plg_task_minimarketreserves.ini',
    'language\en-GB\plg_task_minimarketreserves.sys.ini'
)
foreach($file in $required){
    if(-not (Test-Path -LiteralPath (Join-Path $source $file) -PathType Leaf)){
        throw "MINIMARKET_TASK_MISSING_FILE: $file"
    }
}
if([string]::IsNullOrWhiteSpace($OutputPath)){
    $OutputPath=Join-Path $root "dist\plg_task_minimarketreserves-$version.zip"
}
$OutputPath=[System.IO.Path]::GetFullPath($OutputPath)
New-Item -ItemType Directory -Path (Split-Path -Parent $OutputPath) -Force | Out-Null
if(Test-Path -LiteralPath $OutputPath){Remove-Item -LiteralPath $OutputPath -Force}
Compress-Archive -Path (Join-Path $source '*') -DestinationPath $OutputPath -Force
Add-Type -AssemblyName System.IO.Compression
$archive=[System.IO.Compression.ZipFile]::OpenRead($OutputPath)
try{
    $members=@($archive.Entries | ForEach-Object { $_.FullName.Replace('\','/') })
    foreach($file in $required){
        $expected=$file.Replace('\','/')
        if($members -notcontains $expected){
            throw "MINIMARKET_TASK_ARCHIVE_MISSING: $expected"
        }
    }
}finally{$archive.Dispose()}
Write-Output "MINIMARKET_TASK_PLUGIN_ZIP_OK: $OutputPath"
 -or $componentVersion -notmatch '^\d+\.\d+\.\d+
if([string]$plugin.extension.type -ne 'plugin' -or [string]$plugin.extension.group -ne 'task'){
    throw 'MINIMARKET_TASK_MANIFEST_INVALID'
}
$required=@(
    'minimarketreserves.xml',
    'services\provider.php',
    'src\Extension\Minimarketreserves.php',
    'language\ru-RU\plg_task_minimarketreserves.ini',
    'language\ru-RU\plg_task_minimarketreserves.sys.ini',
    'language\en-GB\plg_task_minimarketreserves.ini',
    'language\en-GB\plg_task_minimarketreserves.sys.ini'
)
foreach($file in $required){
    if(-not (Test-Path -LiteralPath (Join-Path $source $file) -PathType Leaf)){
        throw "MINIMARKET_TASK_MISSING_FILE: $file"
    }
}
if([string]::IsNullOrWhiteSpace($OutputPath)){
    $OutputPath=Join-Path $root "dist\plg_task_minimarketreserves-$version.zip"
}
$OutputPath=[System.IO.Path]::GetFullPath($OutputPath)
New-Item -ItemType Directory -Path (Split-Path -Parent $OutputPath) -Force | Out-Null
if(Test-Path -LiteralPath $OutputPath){Remove-Item -LiteralPath $OutputPath -Force}
Compress-Archive -Path (Join-Path $source '*') -DestinationPath $OutputPath -Force
Add-Type -AssemblyName System.IO.Compression
$archive=[System.IO.Compression.ZipFile]::OpenRead($OutputPath)
try{
    $members=@($archive.Entries | ForEach-Object { $_.FullName.Replace('\','/') })
    foreach($file in $required){
        $expected=$file.Replace('\','/')
        if($members -notcontains $expected){
            throw "MINIMARKET_TASK_ARCHIVE_MISSING: $expected"
        }
    }
}finally{$archive.Dispose()}
Write-Output "MINIMARKET_TASK_PLUGIN_ZIP_OK: $OutputPath"
){
    throw "MINIMARKET_TASK_VERSION_INVALID: component=$componentVersion task=$version"
}
# Scheduler plugin is installed independently; do not reversion/reinstall on
# ordinary component updates unless its own code or contract changed.
if([string]$plugin.extension.type -ne 'plugin' -or [string]$plugin.extension.group -ne 'task'){
    throw 'MINIMARKET_TASK_MANIFEST_INVALID'
}
$required=@(
    'minimarketreserves.xml',
    'services\provider.php',
    'src\Extension\Minimarketreserves.php',
    'language\ru-RU\plg_task_minimarketreserves.ini',
    'language\ru-RU\plg_task_minimarketreserves.sys.ini',
    'language\en-GB\plg_task_minimarketreserves.ini',
    'language\en-GB\plg_task_minimarketreserves.sys.ini'
)
foreach($file in $required){
    if(-not (Test-Path -LiteralPath (Join-Path $source $file) -PathType Leaf)){
        throw "MINIMARKET_TASK_MISSING_FILE: $file"
    }
}
if([string]::IsNullOrWhiteSpace($OutputPath)){
    $OutputPath=Join-Path $root "dist\plg_task_minimarketreserves-$version.zip"
}
$OutputPath=[System.IO.Path]::GetFullPath($OutputPath)
New-Item -ItemType Directory -Path (Split-Path -Parent $OutputPath) -Force | Out-Null
if(Test-Path -LiteralPath $OutputPath){Remove-Item -LiteralPath $OutputPath -Force}
Compress-Archive -Path (Join-Path $source '*') -DestinationPath $OutputPath -Force
Add-Type -AssemblyName System.IO.Compression
$archive=[System.IO.Compression.ZipFile]::OpenRead($OutputPath)
try{
    $members=@($archive.Entries | ForEach-Object { $_.FullName.Replace('\','/') })
    foreach($file in $required){
        $expected=$file.Replace('\','/')
        if($members -notcontains $expected){
            throw "MINIMARKET_TASK_ARCHIVE_MISSING: $expected"
        }
    }
}finally{$archive.Dispose()}
Write-Output "MINIMARKET_TASK_PLUGIN_ZIP_OK: $OutputPath"
