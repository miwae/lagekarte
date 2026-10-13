param(
    [Parameter(Mandatory = $true)]
    [string]$Server,

    [Parameter(Mandatory = $false)]
    [string]$User = 'root',

    [Parameter(Mandatory = $false)]
    [string]$RemoteMuDir = '/var/www/html/wp-content/mu-plugins'
)

$ErrorActionPreference = 'Stop'

$localFile = 'deploy/wordpress/planungs-tool-pages-bootstrap.php'
$remoteFile = "$RemoteMuDir/planungs-tool-pages-bootstrap.php"
$target = "$User@$Server"

if (-not (Test-Path $localFile)) {
    throw "Local file not found: $localFile"
}

Write-Host "Ensuring remote MU plugin directory exists..."
ssh $target "mkdir -p $RemoteMuDir && chown -R www-data:www-data $RemoteMuDir"

Write-Host "Uploading pages bootstrap plugin..."
scp $localFile "$target`:$remoteFile"

Write-Host "Done. Next step: login once to https://planungs-tool.de/wp-admin/ as admin to trigger sync."
Write-Host "Then verify: https://planungs-tool.de/einsatz-tools/"
