param([switch]$Strict)
Set-StrictMode -Version Latest
$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
$component=Join-Path $root 'src\com_minimarket'
$missing=New-Object System.Collections.Generic.List[string]
$counts=@{}

foreach($area in @('admin','site')) {
    $ru=Join-Path $component "$area\language\ru-RU\com_minimarket.ini"
    $en=Join-Path $component "$area\language\en-GB\com_minimarket.ini"
    $keysets=@()
    foreach($p in @($ru,$en)) {
        if(-not (Test-Path -LiteralPath $p -PathType Leaf)) { throw "LANG_FILE_MISSING: $p" }
        $source=Get-Content -LiteralPath $p -Raw -Encoding UTF8
        $keys=@([regex]::Matches($source,'(?m)^(COM_MINIMARKET_[A-Z0-9_]+)=') |
            ForEach-Object {$_.Groups[1].Value})
        if($keys.Count -ne @($keys | Sort-Object -Unique).Count) {
            throw "LANG_DUPLICATE_KEY: $p"
        }
        $keysets+=,@($keys | Sort-Object -Unique)
    }
    if(($keysets[0] -join '|') -ne ($keysets[1] -join '|')) {
        $onlyRu=@($keysets[0] | Where-Object {$_ -notin $keysets[1]})
        $onlyEn=@($keysets[1] | Where-Object {$_ -notin $keysets[0]})
        throw "LANG_LOCALE_PARITY_FAIL: $area RU-only=$($onlyRu -join ',') EN-only=$($onlyEn -join ',')"
    }
    $known=@{}
    foreach($key in $keysets[0]) {$known[$key]=$true}
    $files=Get-ChildItem -LiteralPath (Join-Path $component $area) -Recurse -File |
        Where-Object {($_.Extension -in @('.php','.xml')) -and
            $_.FullName -notmatch '[\\/]language[\\/]' -and
            $_.FullName -notmatch '[\\/]sql[\\/]'}
    $counts[$area]=0
    foreach($f in $files) {
        $text=Get-Content -LiteralPath $f.FullName -Raw -Encoding UTF8
        $tokens=@([regex]::Matches($text,'COM_MINIMARKET_[A-Z0-9_]+') |
            ForEach-Object {$_.Value} | Sort-Object -Unique)
        foreach($token in $tokens) {
            if($token.EndsWith('_')) {continue}
            if(-not $known.ContainsKey($token)) {
                $missing.Add("$area : $token : $($f.Name)")
            }
        }
        $counts[$area]++
    }
}
foreach($item in $missing) {Write-Output "LANG_UNRESOLVED: $item"}
Write-Output "LANG_AUDIT: admin_files=$($counts['admin']) site_files=$($counts['site']) missing=$($missing.Count)"
if($missing.Count -gt 0 -and $Strict) {
    throw "MINIMARKET_TRANSLATIONS_INCOMPLETE: $($missing.Count)"
}
