param([string]$SiteRoot = '')
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
if ([string]::IsNullOrWhiteSpace($SiteRoot)) {
    $SiteRoot = Join-Path $projectRoot 'public_html'
}
if (-not (Test-Path -LiteralPath (Join-Path $SiteRoot 'configuration.php') -PathType Leaf)) {
    throw 'MINIMARKET_DEMO_IMAGES_JOOMLA_NOT_FOUND'
}
$target = Join-Path $SiteRoot 'images\minimarket'
New-Item -ItemType Directory -Path $target -Force | Out-Null
Add-Type -AssemblyName System.Drawing
function Pt([int]$x,[int]$y) { return [System.Drawing.Point]::new($x,$y) }
function Render-Demo([string]$file,[string]$kind,[string]$hex,[string]$title,[bool]$replace) {
    $dest = Join-Path $target $file
    if (-not $replace -and (Test-Path -LiteralPath $dest -PathType Leaf)) { return }
    $bm = [System.Drawing.Bitmap]::new(640,640)
    $g = [System.Drawing.Graphics]::FromImage($bm)
    try {
        $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
        $rect = [System.Drawing.Rectangle]::new(0,0,640,640)
        $bg = [System.Drawing.Drawing2D.LinearGradientBrush]::new(
            $rect,[System.Drawing.Color]::FromArgb(253,253,255),
            [System.Drawing.Color]::FromArgb(225,234,245),45.0)
        $g.FillRectangle($bg,$rect)
        $bg.Dispose()
        $c = [System.Drawing.ColorTranslator]::FromHtml($hex)
        $solid = [System.Drawing.SolidBrush]::new($c)
        $darker = [System.Drawing.Color]::FromArgb([int]($c.R * 0.72),[int]($c.G * 0.72),[int]($c.B * 0.72))
        $dark = [System.Drawing.SolidBrush]::new($darker)
        $white = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::FromArgb(250,250,250))
        $shadow = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::FromArgb(35,40,55,70))
        $g.FillEllipse($shadow,140,539,360,42)
        if ($kind -eq 'shirt') {
            $points = [System.Drawing.Point[]]@(
                (Pt 251 135),(Pt 306 121),(Pt 333 143),(Pt 363 143),
                (Pt 391 121),(Pt 445 135),(Pt 540 232),(Pt 480 298),
                (Pt 439 267),(Pt 448 528),(Pt 249 528),(Pt 257 267),
                (Pt 217 298),(Pt 157 232)
            )
            $g.FillPolygon($solid,$points)
            $border = [System.Drawing.Pen]::new([System.Drawing.Color]::FromArgb(90,15,24,42),3)
            $g.DrawPolygon($border,$points)
            $border.Dispose()
            $g.FillEllipse($dark,299,122,101,53)
            $g.FillEllipse($white,318,127,63,26)
            $highlight = [System.Drawing.Pen]::new([System.Drawing.Color]::FromArgb(60,255,255,255),6)
            $g.DrawLine($highlight,272,193,278,465)
            $g.DrawLine($highlight,427,194,418,465)
            $highlight.Dispose()
        } elseif ($kind -eq 'mug') {
            $handle = [System.Drawing.Pen]::new($c,46)
            $g.DrawArc($handle,387,205,153,221,-88,182)
            $handle.Dispose()
            $g.FillRectangle($solid,194,217,236,267)
            $g.FillEllipse($solid,194,193,236,62)
            $g.FillEllipse($dark,194,446,236,72)
            $g.FillEllipse($white,201,193,222,55)
            $g.FillEllipse($dark,217,205,189,33)
            $gloss = [System.Drawing.Pen]::new([System.Drawing.Color]::FromArgb(115,255,255,255),15)
            $g.DrawLine($gloss,229,266,229,431)
            $gloss.Dispose()
        } else {
            # Clear and distinct stylized toy/gift mock-up for missing legacy demo images.
            $g.FillEllipse($solid,192,142,256,301)
            $g.FillEllipse($dark,181,126,108,106)
            $g.FillEllipse($dark,350,126,108,106)
            $g.FillEllipse($solid,192,151,256,280)
            $g.FillEllipse($white,248,272,144,120)
            $eye = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::FromArgb(50,52,60))
            $g.FillEllipse($eye,253,261,19,22);$g.FillEllipse($eye,369,261,19,22)
            $g.FillEllipse($eye,309,310,23,20)
            $eye.Dispose()
            $g.FillEllipse($dark,205,405,101,95)
            $g.FillEllipse($dark,336,405,101,95)
        }
        $font = [System.Drawing.Font]::new('Arial',15,[System.Drawing.FontStyle]::Bold)
        $small = [System.Drawing.Font]::new('Arial',11)
        $ink = [System.Drawing.SolidBrush]::new([System.Drawing.Color]::FromArgb(55,65,85))
        $g.DrawString($title,$font,$ink,22,22)
        $g.DrawString('MiniMarket / DEMO',$small,$ink,22,599)
        $bm.Save($dest,[System.Drawing.Imaging.ImageFormat]::Png)
        $font.Dispose();$small.Dispose();$ink.Dispose()
        $solid.Dispose();$dark.Dispose();$white.Dispose();$shadow.Dispose()
        Write-Output "MINIMARKET_DEMO_IMAGE_OK: $file"
    } finally { $g.Dispose();$bm.Dispose() }
}

# These pre-existing demo references were SQL-only; materialize missing files.
$palette = @('#b58969','#f4f0e8','#957154','#d0ab70','#5b7baa',
    '#b7a3da','#ed9953','#8a9bae','#8bbd86','#586779',
    '#c79cdb','#b4c9dd','#eb9965','#bba0aa','#d9a47e',
    '#dfbd88','#d1947b','#8bbae2','#cca5cc','#bbd0a3')
for ($i=1; $i -le 20; $i++) {
    $name = 'sample-{0:D2}.png' -f $i
    Render-Demo $name 'toy' $palette[$i-1] ('Demo toy {0:D2}' -f $i) $false
}
foreach ($item in @(
    @('demo-tee-blue-front.png','shirt','#1959bf','T-shirt BLUE'),
    @('demo-tee-blue-detail.png','shirt','#2a68ca','Blue / second view'),
    @('demo-tee-white-front.png','shirt','#f2efe6','T-shirt WHITE'),
    @('demo-tee-white-detail.png','shirt','#dfd9ce','White / second view'),
    @('demo-tee-black-front.png','shirt','#222a37','T-shirt BLACK'),
    @('demo-tee-black-detail.png','shirt','#303a49','Black / second view'),
    @('demo-mug-white-front.png','mug','#eee7dd','Mug WHITE'),
    @('demo-mug-white-detail.png','mug','#ded9d3','White mug / detail'),
    @('demo-mug-red-front.png','mug','#c93f40','Mug RED'),
    @('demo-mug-red-detail.png','mug','#d25959','Red mug / detail')
)) {
    Render-Demo $item[0] $item[1] $item[2] $item[3] $true
}
Write-Output "MINIMARKET_DEMO_IMAGES_READY: $target"
