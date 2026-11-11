param()
Set-StrictMode -Version Latest
$ErrorActionPreference='Stop'
$root=Split-Path -Parent $PSScriptRoot
$component=Join-Path $root 'src\com_minimarket'

function Check([bool]$valid,[string]$name) {
    if(-not $valid){throw "MINIMARKET_089_CHECK_FAILED: $name"}
}
function Source([string]$relative) {
    $file=Join-Path $component $relative
    Check (Test-Path -LiteralPath $file -PathType Leaf) "missing $relative"
    return Get-Content -LiteralPath $file -Raw -Encoding UTF8
}
$manifest=Source 'minimarket.xml'
$config=Source 'admin\config.xml'
[xml]$manifestXml=$manifest
[xml]$configXml=$config
Check ([string]$manifestXml.extension.version -eq '0.5.89') 'component version'
Check ($manifest.Contains('view=deliverymethods')) 'delivery methods menu'
Check ($manifest.Contains('view=paymentmethods')) 'payment methods menu'
Check (-not $manifest.Contains('<menu link="option=com_minimarket&amp;view=deliveryzones"')) 'zone shortcut removed'
Check ($config.Contains('name="delivery_methods"')) 'delivery settings tab'
Check ($config.Contains('name="payment_methods"')) 'payment settings tab'
Check ($config.Contains('name="payment_bank_instructions"')) 'bank instructions'
Check (-not $config.Contains('<option value="cdek">')) 'unimplemented CDEK is not built-in'
$dashboard=Source 'admin\tmpl\dashboard\default.php'
Check ($dashboard.Contains("'view' => 'deliverymethods'")) 'delivery settings dashboard tile'
Check ($dashboard.Contains("'view' => 'paymentmethods'")) 'payment settings dashboard tile'
Check (-not $dashboard.Contains("'view' => 'deliveryzones'")) 'zone dashboard tile removed'

foreach($relative in @(
    'admin\tmpl\seofilter\create.php',
    'admin\tmpl\promocode\create.php',
    'admin\tmpl\deliveryzone\edit.php'
)) {
    $source=Source $relative
    Check ($source.Contains("uitab.startTabSet")) "native tabs $relative"
    Check ($source.Contains("uitab.endTabSet")) "closed tabs $relative"
    Check (-not $source.Contains('data-bs-toggle="tab"')) "no unsupported tabs $relative"
    Check (-not $source.Contains('JSAVEANDCLOSE')) "save button translated $relative"
}
$import=Source 'admin\tmpl\import\default.php'
Check (-not $import.Contains('COM_MINIMARKET_EXCHANGE_FULL_TITLE')) 'remove full JSON block'
$integration=Source 'admin\tmpl\integrations\default.php'
Check (-not $integration.Contains('COM_MINIMARKET_INTEGRATIONS_MANAGE_PLUGINS')) 'remove global plugin link'
Check ($integration.Contains('filter_type')) 'plugin purpose filter'
$search=Source 'admin\tmpl\shared\searchtools.php'
Check ($search.Contains('COM_MINIMARKET_SEARCH_FILTER_OPTIONS')) 'translated filter toggler'
$feeds=Source 'admin\tmpl\feeds\default.php'
Check ($feeds.Contains('filter_provider')) 'feed destination filter'
$zones=Source 'admin\tmpl\deliveryzones\default.php'
Check ($zones.Contains('filter_rate')) 'delivery rate mode filter'

$phpFiles=@(
    'admin\src\Service\MethodSettings.php',
    'admin\src\Service\InstalledMethodPlugins.php',
    'admin\src\Controller\MethodsettingsController.php',
    'admin\src\View\Deliverymethods\HtmlView.php',
    'admin\src\View\Paymentmethods\HtmlView.php',
    'admin\tmpl\deliverymethods\default.php',
    'admin\tmpl\paymentmethods\default.php',
    'admin\tmpl\seofilter\create.php',
    'admin\tmpl\promocode\create.php',
    'admin\tmpl\deliveryzone\edit.php',
    'admin\tmpl\shared\reference-picker.php',
    'admin\tmpl\shared\searchtools.php',
    'admin\tmpl\integrations\default.php',
    'admin\tmpl\feeds\default.php',
    'admin\tmpl\import\default.php',
    'site\src\View\Ordercomplete\HtmlView.php',
    'site\tmpl\ordercomplete\default.php'
)
foreach($relative in $phpFiles){
    $null=Source $relative
}
Write-Output 'MINIMARKET_089_UI_AND_METHODS_STATIC_PASS'

$php=Get-Command php -ErrorAction SilentlyContinue
if($null -eq $php){
    Write-Output 'MINIMARKET_089_PHP_LINT_SKIPPED: php executable not on PATH'
} else {
    foreach($relative in $phpFiles){
        $file=Join-Path $component $relative
        $result=& $php.Source -l $file 2>&1
        if($LASTEXITCODE -ne 0){
            Write-Output $result
            throw "MINIMARKET_089_PHP_LINT_FAILED: $relative"
        }
    }
    & $php.Source (Join-Path $PSScriptRoot 'verify-method-settings.php')
    if($LASTEXITCODE -ne 0){throw 'MINIMARKET_089_METHOD_RULES_FAILED'}
    Write-Output "MINIMARKET_089_PHP_LINT_PASS: files=$($phpFiles.Count)"
}
