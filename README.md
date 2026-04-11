# Lagekarte

Webbasierte taktische Lagekarte (Leaflet) fuer Einsatzfuehrung mit:

- Taktischen Zeichen (Drag and Drop, Popup-Infos)
- TETRA-Datenbank (HRT, MRT, FRT, Zelle, Repeater)
- Druckbereich-Auswahl fuer definierte Kartenausschnitte
- OwnTracks-Integration mit Tracker-Verwaltung (Alias, Farbe, Ausblenden)
- Koordinaten-Suche und Umrechnung (WGS84, UTM, MGRS, GK)
- OSM-POI-Layer inkl. lokalem Cache
- Zeichentools (Linie, Pfeil, Polygon, Rechteck, Kreis, Text)
- Planquadrat-Gitter

## Projektstatus

Die produktive Datei ist aktuell:

- `index.server.live.html`

## Verzeichnisstruktur

- `index.server.live.html` - Hauptanwendung (Frontend)
- `poi_store.php` - einfacher JSON-Cache fuer OSM-POI-Daten
- `deploy/apache/lagekarte-security.conf` - Apache-Sicherheitskonfiguration
- `docs/security-checklist.md` - Security-Checkliste
- `docs/security-ci.md` - Hinweise zur CI-Sicherheitspruefung
- `SECURITY.md` - Security Policy

## Lokale Nutzung

Da die App als Single-File aufgebaut ist, kann sie direkt im Browser geoeffnet werden.
Fuer API-Funktionen (OwnTracks/POI-Cache) empfiehlt sich ein lokaler Webserver.

Beispiel (PHP Built-in Server):

```bash
php -S 127.0.0.1:8080
```

Dann im Browser:

- `http://127.0.0.1:8080/index.server.live.html`

## POI-Cache API (`poi_store.php`)

### GET

`GET /poi_store.php?action=get&type=<typ>&bbox=<bbox>`

Antwort:

```json
{
  "ok": true,
  "elements": [],
  "count": 0
}
```

### POST

`POST /poi_store.php`

Body:

```json
{
  "action": "save",
  "type": "hospital",
  "bbox": "52.1,7.1,52.2,7.2",
  "elements": []
}
```

## OwnTracks

Das Frontend fragt einen WordPress-AJAX-Endpunkt ab:

- `action=owntracks_get`

Konfigurationswerte stehen in `index.server.live.html`:

- `OT_ENDPOINT`
- `OT_KEY`

Hinweis: Tracker werden aus den Backend-Daten erkannt. Die Tracker-Verwaltung im UI verwaltet Anzeige-Optionen (Alias/Farbe/Sichtbarkeit) lokal im Browser.

## Deployment (Live)

Aktueller Live-Pfad auf dem Server:

- `/var/www/html/lagekarte2/index.html`

Typischer Upload:

```bash
scp index.server.live.html root@<server>:/var/www/html/lagekarte2/index.html
```

## Backup

Empfohlen vor jedem Deployment:

```bash
ssh root@<server> 'mkdir -p /var/www/html/lagekarte2/backups; ts=$(date +%Y%m%d_%H%M%S); cp /var/www/html/lagekarte2/index.html /var/www/html/lagekarte2/backups/index_$ts.html'
```

## Sicherheit

Bitte die Vorgaben in `SECURITY.md` sowie den Dateien unter `docs/` beachten.