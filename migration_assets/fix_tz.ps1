$srcPath = 'C:\Users\miwae\Documents\GitHub\lagekarte\migration_assets\taktische-zeichen-lp-v2.html'
$dstPath = 'C:\Users\miwae\Documents\GitHub\lagekarte\migration_assets\taktische-zeichen-lp-v2-2.html'

$bytes = [System.IO.File]::ReadAllBytes($srcPath)
$content = [System.Text.Encoding]::UTF8.GetString($bytes)

# BOM entfernen
if ($content.Length -gt 0 -and [int][char]$content[0] -eq 65279) {
    $content = $content.Substring(1)
    Write-Host "BOM entfernt"
}

# Style-Ende finden
$styleEnd = $content.IndexOf('</style>') + 8
Write-Host "style endet bei $styleEnd"

$styleBlock = $content.Substring(0, $styleEnd)
$body = $content.Substring($styleEnd)

$header = '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
$midHead = '</head><body>'
$footer = '</body></html>'

$enc = New-Object System.Text.UTF8Encoding($false)
$outBytes = $enc.GetBytes($header + $styleBlock + $midHead + $body + $footer)
[System.IO.File]::WriteAllBytes($dstPath, $outBytes)

$check = [System.IO.File]::ReadAllBytes($dstPath)
$checkStr = [System.Text.Encoding]::UTF8.GetString($check)
Write-Host "OK: Bytes=$($check.Length) Umlaute=$([regex]::Matches($checkStr,'[äöüÄÖÜß]').Count) Mojibake=$([regex]::Matches($checkStr,'Ã¼|Ã¶').Count)"
Write-Host $checkStr.Substring(0, 120)
