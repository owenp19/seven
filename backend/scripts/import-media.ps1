# import-media.ps1
#
# Downloads real, freely-licensed photos from Wikimedia Commons into
# storage/app/public so the SEVEN seeders can reference local files with
# SEO file names (lowercase, hyphen-separated, e.g. banners/san-andres.jpg).
#
# Every destination can try several search queries in order; the first
# usable image wins. Skipped silently if the target file already exists,
# so the script is idempotent.
#
# Run from the backend folder:
#   powershell -ExecutionPolicy Bypass -File scripts/import-media.ps1
#
# The seeders fall back to branded SVGs for any slot left empty, so a
# missing download never breaks the app.

$ErrorActionPreference = 'Stop'

$root = Join-Path $PSScriptRoot '..'
$targetRoot = Join-Path $root 'storage\app\public'
$queriesFile = Join-Path $PSScriptRoot 'media-queries.json'

if (-not (Test-Path -LiteralPath $targetRoot)) {
    New-Item -ItemType Directory -Path $targetRoot | Out-Null
}

$agent = 'SEVENApp/1.0 (https://seven.app; tourism demo data loader)'

function Get-CommonsImageUrl {
    param([string]$Search)

    $api = 'https://commons.wikimedia.org/w/api.php'
    $params = @{
        action    = 'query'
        generator = 'search'
        gsrsearch = $Search
        gsrnamespace = 6
        gsrlimit  = 8
        prop      = 'imageinfo'
        iiprop    = 'url|mime'
        iiurlwidth = 1600
        format    = 'json'
    }

    try {
        $response = Invoke-RestMethod -Uri $api -Method Get -Body $params -Headers @{ 'User-Agent' = $agent } -TimeoutSec 30
    } catch {
        return $null
    }

    if (-not $response.query) { return $null }

    foreach ($page in $response.query.pages.PSObject.Properties) {
        $info = $page.Value.imageinfo[0]
        if (-not $info.thumburl) { continue }
        if ($info.mime -notmatch '^image/(jpeg|png|webp)$') { continue }
        return $info.thumburl
    }

    return $null
}

$manifest = Get-Content -LiteralPath $queriesFile -Raw | ConvertFrom-Json

$downloaded = 0
$skipped = 0
$failed = @()

foreach ($entry in $manifest.destinations) {
    $relative = $entry.destination
    $target = Join-Path $targetRoot $relative
    $dir = Split-Path -Parent $target

    if (-not (Test-Path -LiteralPath $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }

    if (Test-Path -LiteralPath $target) {
        Write-Host "SKIP  $relative"
        $skipped++
        continue
    }

    $ok = $false
    foreach ($query in $entry.queries) {
        $url = Get-CommonsImageUrl -Search $query
        if (-not $url) { continue }

        try {
            Invoke-WebRequest -Uri $url -OutFile $target -UseBasicParsing -Headers @{ 'User-Agent' = $agent } -TimeoutSec 60
            Write-Host ("OK    $relative  <-  $query  ({0,6:N0} KB)" -f ((Get-Item -LiteralPath $target).Length / 1KB))
            $downloaded++
            $ok = $true
            break
        } catch {
            Write-Host "WARN   failed download from query '$query' for $relative"
        }
    }

    if (-not $ok) {
        Write-Host "FAIL  $relative"
        $failed += $relative
    }
}

Write-Host ""
Write-Host "Downloaded: $downloaded | Skipped: $skipped | Failed: $($failed.Count)"
if ($failed.Count -gt 0) {
    Write-Host "Missing slots (seeders will use the branded SVG fallback):"
    $failed | ForEach-Object { Write-Host "  - $_" }
}