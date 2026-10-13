$ErrorActionPreference = 'Stop'

$root = 'C:\Users\miwae\Documents\GitHub\lagekarte\migration_assets'
$urlListPath = Join-Path $root 'gf_missing_urls.txt'
$outDir = Join-Path $root 'gf_assets'
$mapPath = Join-Path $root 'gf_asset_map.csv'

New-Item -ItemType Directory -Force -Path $outDir | Out-Null
$urls = Get-Content $urlListPath | Where-Object { $_ -and $_.Trim() }

$extByContentType = @{
    'image/png' = '.png'
    'image/jpeg' = '.jpg'
    'image/jpg' = '.jpg'
    'image/gif' = '.gif'
    'image/svg+xml' = '.svg'
    'image/webp' = '.webp'
    'image/bmp' = '.bmp'
    'text/plain' = '.txt'
    'application/octet-stream' = ''
}

$results = New-Object System.Collections.Generic.List[object]
$index = 0
foreach ($url in $urls) {
    $index++
    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing
        $uri = [Uri]$url
        $relativePath = $uri.AbsolutePath -replace '^/wp-content/uploads/', ''
        $relativeDir = Split-Path $relativePath -Parent
        $leaf = [System.IO.Path]::GetFileName($relativePath)
        $ext = [System.IO.Path]::GetExtension($leaf)
        $contentType = (($response.Headers['Content-Type'] -split ';')[0]).Trim().ToLowerInvariant()
        if (-not $ext -and $extByContentType.ContainsKey($contentType)) {
            $ext = $extByContentType[$contentType]
        }
        if (-not $ext) {
            $ext = '.bin'
        }
        if (-not [System.IO.Path]::GetExtension($leaf)) {
            $leaf = $leaf + $ext
        }
        $targetDir = Join-Path $outDir $relativeDir
        New-Item -ItemType Directory -Force -Path $targetDir | Out-Null
        $targetPath = Join-Path $targetDir $leaf
        $counter = 1
        while (Test-Path $targetPath) {
            $baseName = [System.IO.Path]::GetFileNameWithoutExtension($leaf)
            $realExt = [System.IO.Path]::GetExtension($leaf)
            $targetPath = Join-Path $targetDir ('{0}__{1}{2}' -f $baseName, $counter, $realExt)
            $counter++
        }
        [System.IO.File]::WriteAllBytes($targetPath, $response.RawContentStream.ToArray())
        $results.Add([pscustomobject]@{
            OldUrl = $url
            RelativePath = ((Resolve-Path $targetPath).Path.Substring($outDir.Length + 1) -replace '\\','/')
            ContentType = $contentType
            Bytes = (Get-Item $targetPath).Length
        }) | Out-Null
        if (($index % 20) -eq 0 -or $index -eq $urls.Count) {
            Write-Host ('Downloaded {0}/{1}' -f $index, $urls.Count)
        }
    } catch {
        Write-Host ('FAILED ' + $url + ' :: ' + $_.Exception.Message)
        $results.Add([pscustomobject]@{
            OldUrl = $url
            RelativePath = ''
            ContentType = 'ERROR'
            Bytes = 0
        }) | Out-Null
    }
}

$results | Export-Csv -Path $mapPath -NoTypeInformation -Encoding UTF8
Write-Host ('Saved map: ' + $mapPath)
Write-Host ('Success: ' + (($results | Where-Object { $_.RelativePath }).Count))
Write-Host ('Failed: ' + (($results | Where-Object { -not $_.RelativePath }).Count))
