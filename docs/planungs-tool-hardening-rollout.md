# Planungs-Tool Hardening Rollout

## 1) Apache config deploy

1. Copy Apache profile to server:

```bash
scp deploy/apache/lagekarte-security.conf root@<server>:/etc/apache2/sites-available/planungs-tool-security.conf
```

2. Enable site and required modules:

```bash
ssh root@<server> '
  a2enmod headers ssl rewrite &&
  a2ensite planungs-tool-security.conf &&
  apachectl configtest &&
  systemctl reload apache2
'
```

3. Add recommended global directives in `/etc/apache2/apache2.conf`:

```apache
ServerTokens Prod
ServerSignature Off
TraceEnable Off
```

4. Reload Apache after global changes:

```bash
ssh root@<server> 'apachectl configtest && systemctl reload apache2'
```

## 2) WordPress MU plugin deploy

1. Copy plugin file:

```bash
scp deploy/wordpress/planungs-tool-hardening-mu-plugin.php root@<server>:/var/www/html/wp-content/mu-plugins/planungs-tool-hardening.php
```

2. Ensure mu-plugins directory exists:

```bash
ssh root@<server> 'mkdir -p /var/www/html/wp-content/mu-plugins && chown -R www-data:www-data /var/www/html/wp-content/mu-plugins'
```

## 3) Verification checks

Run after deploy:

```powershell
$urls = @(
  'https://planungs-tool.de/',
  'https://planungs-tool.de/wp-login.php',
  'https://planungs-tool.de/wp-json/',
  'https://planungs-tool.de/wp-json/wp/v2/users',
  'https://planungs-tool.de/?author=1',
  'https://planungs-tool.de/xmlrpc.php'
)

foreach ($u in $urls) {
  try {
    $r = Invoke-WebRequest -UseBasicParsing -Uri $u -Method GET -MaximumRedirection 5 -TimeoutSec 20
    "$u`t$($r.StatusCode)`t$($r.BaseResponse.ResponseUri.AbsoluteUri)"
  } catch {
    if ($_.Exception.Response) {
      "$u`t$([int]$_.Exception.Response.StatusCode)"
    } else {
      "$u`tERROR`t$($_.Exception.Message)"
    }
  }
}
```

Expected:
- `/wp-json/wp/v2/users` => 401 or 403
- `/?author=1` => redirect to `/`
- `/xmlrpc.php` => 403
- HSTS, nosniff, frame and referrer headers present on `/`
