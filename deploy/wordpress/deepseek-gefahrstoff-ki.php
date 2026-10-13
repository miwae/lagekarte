<?php
/**
 * Plugin Name: KI-Gefahrstoffassistent
 * Description: DeepSeek-basierter Gefahrstoffassistent für BOS. Shortcode: [gefahrstoff_ki]
 *              API-Key in wp-config.php definieren: define('DEEPSEEK_API_KEY', 'sk-...');
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ══════════════════════════════════════════════════════
   REST-ENDPOINT  POST /wp-json/planungs-tool/v1/gefahrstoff-ki
   ══════════════════════════════════════════════════════ */
add_action( 'rest_api_init', function () {
    register_rest_route( 'planungs-tool/v1', '/gefahrstoff-ki', [
        'methods'             => 'POST',
        'callback'            => 'ptgk_handle',
        'permission_callback' => '__return_true',
        'args'                => [
            'stoff'    => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
            'szenario' => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
            '_nonce'   => [ 'required' => false, 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ],
        ],
    ] );
} );

function ptgk_handle( WP_REST_Request $req ): WP_REST_Response|WP_Error {

    // ── CSRF-Schutz ──────────────────────────────────────────────────────────
    if ( ! wp_verify_nonce( $req->get_param( '_nonce' ), 'ptgk_query' ) ) {
        return new WP_Error( 'forbidden', 'Ungültiger Sicherheits-Token.', [ 'status' => 403 ] );
    }

    // ── IP-Rate-Limit: 15 Anfragen / Stunde ──────────────────────────────────
    $ip  = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    $key = 'ptgk_rl_' . md5( $ip );
    $cnt = (int) get_transient( $key );
    if ( $cnt >= 15 ) {
        return new WP_Error( 'rate_limit', 'Anfragelimit erreicht. Bitte in einer Stunde erneut versuchen.', [ 'status' => 429 ] );
    }
    set_transient( $key, $cnt + 1, HOUR_IN_SECONDS );

    // ── Eingabe ───────────────────────────────────────────────────────────────
    $stoff    = $req->get_param( 'stoff' );
    $szenario = $req->get_param( 'szenario' );

    if ( mb_strlen( $stoff ) > 200 ) {
        return new WP_Error( 'bad_request', 'Eingabe zu lang.', [ 'status' => 400 ] );
    }

    if ( ! defined( 'DEEPSEEK_API_KEY' ) || DEEPSEEK_API_KEY === '' ) {
        return new WP_Error( 'config', 'KI-Dienst nicht konfiguriert (DEEPSEEK_API_KEY fehlt).', [ 'status' => 503 ] );
    }

    // ── System-Prompt ─────────────────────────────────────────────────────────
    $system = 'Du bist ein Gefahrstoffassistent für BOS-Einsatzkräfte (Feuerwehr, THW, Rettungsdienst) in Deutschland. '
            . 'Beantworte Anfragen zu Gefahrstoffen strukturiert und präzise auf Deutsch. '
            . 'Orientiere dich an GAMS-Regel, FwDV, GHS/CLP-Verordnung und DGUV-Vorschriften. '
            . 'Antworte AUSSCHLIESSLICH mit einem JSON-Objekt mit folgenden Feldern: '
            . '{'
            .   '"stoff":"Stoffname und wichtige Synonyme",'
            .   '"gefahren":"Hauptgefahren in 2-4 prägnanten Sätzen",'
            .   '"ghs_hinweise":"GHS-Piktogramme (Nummern und Namen), Signalwort, wichtige H-Satz-Kategorien",'
            .   '"erstmassnahmen":"GAMS-orientierte Erstmaßnahmen als nummerierte Liste",'
            .   '"psa":"Empfohlene PSA je nach Einsatzszenario",'
            .   '"loeschmittel":"Geeignete Löschmittel und was NICHT verwendet werden darf (leer wenn kein Brand-Szenario)",'
            .   '"umwelt":"Umweltschutz- und Gewässerschutzmaßnahmen",'
            .   '"cas":"CAS-Nummer falls bekannt, sonst leerer String",'
            .   '"gestis_tipp":"Kurzer Hinweis auf GESTIS-Datenbank mit CAS-Nummer"'
            . '} '
            . 'Kein Text außerhalb des JSON-Objekts. '
            . 'Bei unbekanntem Stoff: stoff="Unbekannter Stoff", erstmassnahmen=Vorgehen bei unbekanntem Stoff nach FwDV.';

    $user_msg = "Stoff: {$stoff}";
    if ( $szenario !== '' ) {
        $user_msg .= "\nEinsatzszenario: {$szenario}";
    }

    // ── DeepSeek API-Aufruf ────────────────────────────────────────────────────
    $http = wp_remote_post( 'https://api.deepseek.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . DEEPSEEK_API_KEY,
            'Content-Type'  => 'application/json',
        ],
        'body'    => wp_json_encode( [
            'model'       => 'deepseek-chat',
            'temperature' => 0.15,
            'max_tokens'  => 1200,
            'messages'    => [
                [ 'role' => 'system', 'content' => $system ],
                [ 'role' => 'user',   'content' => $user_msg ],
            ],
        ] ),
        'timeout' => 35,
    ] );

    if ( is_wp_error( $http ) ) {
        return new WP_Error( 'api_error', 'KI-Verbindung fehlgeschlagen.', [ 'status' => 502 ] );
    }

    $http_status = (int) wp_remote_retrieve_response_code( $http );
    if ( $http_status !== 200 ) {
        return new WP_Error( 'api_error', "DeepSeek API Fehler (HTTP {$http_status}).", [ 'status' => 502 ] );
    }

    $body    = json_decode( wp_remote_retrieve_body( $http ), true );
    $content = $body['choices'][0]['message']['content'] ?? '';

    // Markdown-Code-Fences entfernen falls vorhanden
    $content = preg_replace( '/^```(?:json)?\s*/i', '', trim( $content ) );
    $content = preg_replace( '/\s*```$/', '', $content );

    $data = json_decode( $content, true );
    if ( ! is_array( $data ) ) {
        return new WP_Error( 'parse_error', 'KI-Antwort konnte nicht verarbeitet werden.', [ 'status' => 502 ] );
    }

    // Alle Felder sanitisieren
    $allowed = [ 'stoff', 'gefahren', 'ghs_hinweise', 'erstmassnahmen', 'psa', 'loeschmittel', 'umwelt', 'cas', 'gestis_tipp' ];
    $safe    = [];
    foreach ( $allowed as $k ) {
        $safe[ $k ] = isset( $data[ $k ] ) ? sanitize_textarea_field( (string) $data[ $k ] ) : '';
    }

    return new WP_REST_Response( $safe, 200 );
}

/* ══════════════════════════════════════════════════════
   SHORTCODE  [gefahrstoff_ki]
   ══════════════════════════════════════════════════════ */
add_shortcode( 'gefahrstoff_ki', 'ptgk_shortcode' );

function ptgk_shortcode(): string {
    $nonce    = wp_create_nonce( 'ptgk_query' );
    $rest_url = rest_url( 'planungs-tool/v1/gefahrstoff-ki' );
    ob_start();
    ?>
<div id="ptgk-wrap">
<style>
#ptgk-wrap *{box-sizing:border-box;font-family:system-ui,sans-serif}
#ptgk-wrap{background:#0a1628;color:#f0f4f8;border-radius:10px;overflow:hidden;
  border:1.5px solid rgba(232,93,4,.25);max-width:860px;margin:0 auto}

/* Header */
#ptgk-hdr{background:#0f0f1a;border-bottom:2px solid #e85d04;padding:.55rem 1rem;
  display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
#ptgk-hdr h2{font-size:.98rem;font-weight:800;margin:0;color:#f0f4f8}
#ptgk-hdr h2 span{color:#e85d04}
.ptgk-badge{font-size:.63rem;background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);
  color:#e85d04;padding:.1rem .4rem;border-radius:3px;white-space:nowrap}

/* Formular */
#ptgk-form{padding:.9rem 1rem;display:flex;flex-direction:column;gap:.55rem;
  border-bottom:1px solid rgba(232,93,4,.1)}
.ptgk-row{display:flex;gap:.6rem;flex-wrap:wrap}
#ptgk-stoff{flex:1;min-width:180px;background:#112240;border:1.5px solid rgba(232,93,4,.2);
  color:#f0f4f8;border-radius:5px;padding:.38rem .65rem;font-size:.84rem}
#ptgk-stoff:focus{outline:none;border-color:#e85d04}
#ptgk-stoff::placeholder{color:#3a5070}
#ptgk-szenario{background:#112240;border:1.5px solid rgba(232,93,4,.2);color:#f0f4f8;
  border-radius:5px;padding:.38rem .55rem;font-size:.82rem;cursor:pointer}
#ptgk-szenario:focus{outline:none;border-color:#e85d04}
#ptgk-szenario option{background:#112240}
#ptgk-btn{background:#e85d04;border:none;color:#fff;padding:.38rem 1.2rem;border-radius:5px;
  cursor:pointer;font-size:.84rem;font-weight:700;white-space:nowrap;transition:background .15s}
#ptgk-btn:hover:not(:disabled){background:#f97316}
#ptgk-btn:disabled{opacity:.45;cursor:not-allowed}
#ptgk-status{font-size:.76rem;color:#4a6080;min-height:1.1em;padding-left:.1rem}

/* Ergebnis-Bereiche */
#ptgk-result{padding:.8rem 1rem;display:none;flex-direction:column;gap:.5rem}
#ptgk-stoff-name{font-size:1rem;font-weight:800;color:#e85d04;padding:.15rem 0 .05rem}
.ptgk-sec{border-radius:6px;padding:.45rem .65rem;font-size:.8rem;line-height:1.6}
.ptgk-sec-lbl{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.6px;margin-bottom:.2rem}
.ptgk-danger{background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);color:#fca5a5}
.ptgk-danger .ptgk-sec-lbl{color:#ef4444}
.ptgk-ghs{background:rgba(232,93,4,.07);border:1px solid rgba(232,93,4,.2);color:#fed7aa}
.ptgk-ghs .ptgk-sec-lbl{color:#e85d04}
.ptgk-action{background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.2);color:#fde68a}
.ptgk-action .ptgk-sec-lbl{color:#f59e0b}
.ptgk-psa{background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);color:#bfdbfe}
.ptgk-psa .ptgk-sec-lbl{color:#60a5fa}
.ptgk-losch{background:rgba(34,197,94,.07);border:1px solid rgba(34,197,94,.2);color:#bbf7d0}
.ptgk-losch .ptgk-sec-lbl{color:#22c55e}
.ptgk-env{background:rgba(20,184,166,.07);border:1px solid rgba(20,184,166,.2);color:#99f6e4}
.ptgk-env .ptgk-sec-lbl{color:#14b8a6}
.ptgk-gestis{background:#0d1b2a;border:1px solid rgba(232,93,4,.15);color:#8ba0b4}
.ptgk-gestis .ptgk-sec-lbl{color:#4a6080}
.ptgk-gestis a{color:#e85d04;text-decoration:none}
.ptgk-gestis a:hover{text-decoration:underline}

/* Disclaimer */
#ptgk-disclaimer{font-size:.65rem;color:#2a4060;padding:.5rem 1rem .6rem;
  border-top:1px solid rgba(232,93,4,.08);line-height:1.55}
</style>

<div id="ptgk-hdr">
  <h2>&#9881;&#65039; KI-Gefahrstoff<span>assistent</span></h2>
  <span class="ptgk-badge">DeepSeek AI</span>
  <span class="ptgk-badge">BOS / FwDV</span>
  <span class="ptgk-badge">GAMS-Regel</span>
</div>

<div id="ptgk-form">
  <div class="ptgk-row">
    <input id="ptgk-stoff" type="text"
      placeholder="&#128269; Stoff, UN-Nr. oder CAS-Nr. (z.B. Benzol, UN 1114, 71-43-2)…"
      autocomplete="off" maxlength="200">
    <select id="ptgk-szenario">
      <option value="">— Einsatzszenario (optional) —</option>
      <option value="Brand">&#128293; Brand</option>
      <option value="Austritt / Freisetzung">&#128168; Austritt / Freisetzung</option>
      <option value="Transportunfall">&#128665; Transportunfall</option>
      <option value="Lagerung / Vorbereitung">&#127981; Lagerung / Vorbereitung</option>
      <option value="Unbekannter Stoff">&#10067; Unbekannter Stoff</option>
    </select>
    <button id="ptgk-btn" onclick="ptgkQuery()">KI&#8209;Analyse</button>
  </div>
  <div id="ptgk-status"></div>
</div>

<div id="ptgk-result">
  <div id="ptgk-stoff-name"></div>

  <div class="ptgk-sec ptgk-danger">
    <div class="ptgk-sec-lbl">&#9888; Gefahren</div>
    <div id="ptgk-gefahren"></div>
  </div>

  <div class="ptgk-sec ptgk-ghs">
    <div class="ptgk-sec-lbl">&#9762; GHS-Hinweise</div>
    <div id="ptgk-ghs"></div>
  </div>

  <div class="ptgk-sec ptgk-action">
    <div class="ptgk-sec-lbl">&#128073; Erstma&#223;nahmen (GAMS)</div>
    <div id="ptgk-erst"></div>
  </div>

  <div class="ptgk-sec ptgk-psa">
    <div class="ptgk-sec-lbl">&#129001; PSA-Empfehlung</div>
    <div id="ptgk-psa"></div>
  </div>

  <div class="ptgk-sec ptgk-losch" id="ptgk-losch-wrap">
    <div class="ptgk-sec-lbl">&#128293; L&#246;schmittel</div>
    <div id="ptgk-losch"></div>
  </div>

  <div class="ptgk-sec ptgk-env">
    <div class="ptgk-sec-lbl">&#127807; Umweltschutz</div>
    <div id="ptgk-umwelt"></div>
  </div>

  <div class="ptgk-sec ptgk-gestis">
    <div class="ptgk-sec-lbl">&#128270; GESTIS-Datenbank</div>
    <div id="ptgk-gestis"></div>
  </div>
</div>

<div id="ptgk-disclaimer">
  &#9888;&#65039; <strong>Hinweis:</strong> KI-generierte Informationen dienen nur der Orientierung
  und ersetzen keine Fachkraft, das Sicherheitsdatenblatt oder offizielle Quellen (GESTIS, ERICard, TUIS).
  Im Einsatz stets eigene Fachkenntnis und Dienstvorschriften anwenden. Keine Haftung f&#252;r
  Vollst&#228;ndigkeit oder Richtigkeit.
</div>
</div>

<script>
(function () {
  var NONCE = <?php echo wp_json_encode( $nonce ); ?>;
  var URL   = <?php echo wp_json_encode( $rest_url ); ?>;

  window.ptgkQuery = function () {
    var stoff = document.getElementById('ptgk-stoff').value.trim();
    if (!stoff) { setStatus('Bitte einen Stoffnamen oder eine Bezeichnung eingeben.'); return; }

    var btn = document.getElementById('ptgk-btn');
    btn.disabled = true;
    document.getElementById('ptgk-result').style.display = 'none';
    setStatus('&#9711; KI analysiert&#8202;… bitte warten.');

    fetch(URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        stoff:    stoff,
        szenario: document.getElementById('ptgk-szenario').value,
        _nonce:   NONCE
      })
    })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      btn.disabled = false;
      if (d.code) { setStatus('&#10060; ' + esc(d.message || 'Unbekannter Fehler')); return; }
      setStatus('');
      showResult(d);
    })
    .catch(function (e) {
      btn.disabled = false;
      setStatus('&#10060; Verbindungsfehler: ' + esc(e.message));
    });
  };

  function setStatus(msg) { document.getElementById('ptgk-status').innerHTML = msg; }

  function esc(s) {
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(String(s)));
    return d.innerHTML;
  }

  function nl2br(s) { return esc(s).replace(/\n/g, '<br>'); }

  function showResult(d) {
    document.getElementById('ptgk-stoff-name').textContent = d.stoff || '';
    document.getElementById('ptgk-gefahren').innerHTML = nl2br(d.gefahren);
    document.getElementById('ptgk-ghs').innerHTML      = nl2br(d.ghs_hinweise);
    document.getElementById('ptgk-erst').innerHTML     = nl2br(d.erstmassnahmen);
    document.getElementById('ptgk-psa').innerHTML      = nl2br(d.psa);
    document.getElementById('ptgk-umwelt').innerHTML   = nl2br(d.umwelt);

    var lw = (d.loeschmittel || '').trim();
    document.getElementById('ptgk-losch-wrap').style.display = lw ? '' : 'none';
    if (lw) document.getElementById('ptgk-losch').innerHTML = nl2br(lw);

    var cas = (d.cas || '').trim();
    var tip = esc(d.gestis_tipp || '');
    var gestisHtml = tip;
    if (cas) {
      var q = encodeURIComponent(cas);
      gestisHtml += (tip ? ' ' : '') +
        '<a href="https://gestis.dguv.de/search?search=' + q + '&lang=de" target="_blank" rel="noopener">' +
        '&#8594; GESTIS: ' + esc(cas) + '</a>';
    }
    document.getElementById('ptgk-gestis').innerHTML = gestisHtml ||
      'Suche in <a href="https://gestis.dguv.de/" target="_blank" rel="noopener">GESTIS-Datenbank</a>';

    document.getElementById('ptgk-result').style.display = 'flex';
  }

  document.getElementById('ptgk-stoff').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') window.ptgkQuery();
  });
})();
</script>
    <?php
    return ob_get_clean();
}
