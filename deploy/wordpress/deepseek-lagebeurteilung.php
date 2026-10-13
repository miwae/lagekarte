<?php
/**
 * Plugin Name: KI-Lagebeurteilung & Nachbesprechung
 * Description: KI-gestützte Lagebeurteilung und Nachbesprechungsprotokoll via DeepSeek.
 *              Shortcodes: [lagebeurteilung_ki] und [nachbesprechung_ki]
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── REST-Endpunkte ──────────────────────────────────────────────────────────

add_action( 'rest_api_init', function () {

    register_rest_route( 'planungs-tool/v1', '/lagebeurteilung', [
        'methods'             => 'POST',
        'callback'            => 'ptlb_lage_callback',
        'permission_callback' => '__return_true',
    ] );

    register_rest_route( 'planungs-tool/v1', '/nachbesprechung', [
        'methods'             => 'POST',
        'callback'            => 'ptlb_nach_callback',
        'permission_callback' => '__return_true',
    ] );
} );

// ─── Hilfsfunktionen ─────────────────────────────────────────────────────────

function ptlb_rate_limit( string $key ): bool {
    $ip   = sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' );
    $tkey = 'ptlb_rl_' . md5( $key . $ip );
    $hits = (int) get_transient( $tkey );
    if ( $hits >= 15 ) {
        return false;
    }
    set_transient( $tkey, $hits + 1, HOUR_IN_SECONDS );
    return true;
}

function ptlb_deepseek( string $system_prompt, string $user_input ): array {
    $api_key = defined( 'DEEPSEEK_API_KEY' ) ? DEEPSEEK_API_KEY : '';
    if ( empty( $api_key ) ) {
        return [ 'error' => 'API-Key nicht konfiguriert (DEEPSEEK_API_KEY in wp-config.php).' ];
    }

    $response = wp_remote_post( 'https://api.deepseek.com/v1/chat/completions', [
        'timeout' => 45,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => wp_json_encode( [
            'model'       => 'deepseek-chat',
            'temperature' => 0.4,
            'messages'    => [
                [ 'role' => 'system', 'content' => $system_prompt ],
                [ 'role' => 'user',   'content' => $user_input ],
            ],
            'response_format' => [ 'type' => 'json_object' ],
        ] ),
    ] );

    if ( is_wp_error( $response ) ) {
        return [ 'error' => $response->get_error_message() ];
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    $content = $body['choices'][0]['message']['content'] ?? '';
    $parsed  = json_decode( $content, true );

    if ( ! is_array( $parsed ) ) {
        return [ 'error' => 'Ungültige Antwort der KI.' ];
    }

    return $parsed;
}

// ─── Lagebeurteilung ─────────────────────────────────────────────────────────

function ptlb_lage_callback( WP_REST_Request $request ): WP_REST_Response {
    if ( ! wp_verify_nonce( $request->get_param( '_nonce' ), 'ptlb_query' ) ) {
        return new WP_REST_Response( [ 'error' => 'Ungültiges Sicherheitstoken.' ], 403 );
    }

    if ( ! ptlb_rate_limit( 'lage' ) ) {
        return new WP_REST_Response( [ 'error' => 'Limit erreicht. Bitte in einer Stunde erneut versuchen.' ], 429 );
    }

    $lage = sanitize_textarea_field( $request->get_param( 'lage' ) );
    if ( strlen( $lage ) < 20 ) {
        return new WP_REST_Response( [ 'error' => 'Bitte die Lage ausführlicher beschreiben (mind. 20 Zeichen).' ], 400 );
    }
    if ( strlen( $lage ) > 4000 ) {
        return new WP_REST_Response( [ 'error' => 'Eingabe zu lang (max. 4000 Zeichen).' ], 400 );
    }

    $system = 'Du bist ein erfahrener Einsatzleiter und Ausbilder im deutschen BOS-Bereich (Feuerwehr, THW, Rettungsdienst). '
        . 'Erstelle eine strukturierte Lagebeurteilung nach dem Führungsvorgang (FwDV 100). '
        . 'Antworte ausschließlich als JSON mit folgenden Feldern: '
        . '"lagefeststellung" (Was ist passiert?), '
        . '"lagebeurteilung" (Analyse, Gefährdungen, Entwicklungsprognose), '
        . '"fuehrungsentschluss" (konkrete Führungsentscheidung), '
        . '"auftragserteilung" (Aufträge an die Abschnitte), '
        . '"massnahmen" (Array von Sofortmaßnahmen, mind. 5), '
        . '"kraefte" (empfohlene Kräfte und Mittel), '
        . '"risiken" (besondere Risiken und Sicherheitshinweise). '
        . 'Antworte auf Deutsch, präzise und praxisnah. Keine Markdown-Formatierung.';

    $result = ptlb_deepseek( $system, $lage );
    return new WP_REST_Response( $result, isset( $result['error'] ) ? 500 : 200 );
}

// ─── Nachbesprechung ─────────────────────────────────────────────────────────

function ptlb_nach_callback( WP_REST_Request $request ): WP_REST_Response {
    if ( ! wp_verify_nonce( $request->get_param( '_nonce' ), 'ptnb_query' ) ) {
        return new WP_REST_Response( [ 'error' => 'Ungültiges Sicherheitstoken.' ], 403 );
    }

    if ( ! ptlb_rate_limit( 'nach' ) ) {
        return new WP_REST_Response( [ 'error' => 'Limit erreicht. Bitte in einer Stunde erneut versuchen.' ], 429 );
    }

    $notizen = sanitize_textarea_field( $request->get_param( 'notizen' ) );
    if ( strlen( $notizen ) < 30 ) {
        return new WP_REST_Response( [ 'error' => 'Bitte mehr Einsatzdetails eingeben (mind. 30 Zeichen).' ], 400 );
    }
    if ( strlen( $notizen ) > 6000 ) {
        return new WP_REST_Response( [ 'error' => 'Eingabe zu lang (max. 6000 Zeichen).' ], 400 );
    }

    $system = 'Du bist ein erfahrener Führungskräfte-Coach und Ausbilder im deutschen BOS-Bereich. '
        . 'Erstelle ein professionelles Nachbesprechungsprotokoll (Debriefing) auf Basis der Einsatznotizen. '
        . 'Antworte ausschließlich als JSON mit folgenden Feldern: '
        . '"zusammenfassung" (Kurzzusammenfassung des Einsatzes), '
        . '"zeitlinie" (wichtige Zeitpunkte und Entscheidungen), '
        . '"positiv" (Array: Was lief gut? mind. 3 Punkte), '
        . '"verbesserung" (Array: Was kann verbessert werden? mind. 3 Punkte), '
        . '"empfehlungen" (Array: konkrete Empfehlungen für künftige Einsätze, mind. 3), '
        . '"protokoll" (formales Nachbesprechungsprotokoll als Fließtext für die Akte). '
        . 'Antworte auf Deutsch, konstruktiv und lösungsorientiert. Keine Markdown-Formatierung.';

    $result = ptlb_deepseek( $system, $notizen );
    return new WP_REST_Response( $result, isset( $result['error'] ) ? 500 : 200 );
}

// ─── Shortcode: Lagebeurteilung ──────────────────────────────────────────────

add_shortcode( 'lagebeurteilung_ki', function (): string {
    ob_start();
    $nonce   = wp_create_nonce( 'ptlb_query' );
    $api_url = esc_url( rest_url( 'planungs-tool/v1/lagebeurteilung' ) );
    ?>
<div id="lbrt">
<style>
#lbrt *{box-sizing:border-box;font-family:system-ui,sans-serif}
#lbrt{background:#0a1628;color:#f0f4f8;border-radius:10px;overflow:hidden;border:1.5px solid rgba(232,93,4,.25);max-width:860px;margin:0 auto}
#lbrt-hdr{background:#0f0f1a;border-bottom:2px solid #e85d04;padding:.55rem 1rem;display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
#lbrt-hdr h2{font-size:.98rem;font-weight:800;margin:0}#lbrt-hdr h2 span{color:#e85d04}
.lbrt-bdg{font-size:.63rem;background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.1rem .4rem;border-radius:3px}
#lbrt-inp-wrap{padding:.75rem 1rem;background:#0d1b2a;border-bottom:1px solid rgba(232,93,4,.08)}
#lbrt-inp-lbl{font-size:.72rem;font-weight:700;color:#8ba0b4;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.3px}
#lbrt-ta{width:100%;background:#112240;border:1.5px solid rgba(232,93,4,.2);color:#f0f4f8;border-radius:6px;padding:.5rem .7rem;font-size:.82rem;resize:vertical;min-height:110px;line-height:1.55}
#lbrt-ta:focus{outline:none;border-color:#e85d04}
#lbrt-ta::placeholder{color:#3a5070}
#lbrt-char{font-size:.65rem;color:#2a4060;text-align:right;margin-top:.2rem}
#lbrt-sub-row{display:flex;align-items:center;gap:.7rem;margin-top:.5rem}
#lbrt-btn{background:#e85d04;border:none;color:#fff;padding:.35rem 1.1rem;border-radius:5px;cursor:pointer;font-size:.82rem;font-weight:700;transition:background .15s}
#lbrt-btn:hover{background:#f97316}#lbrt-btn:disabled{opacity:.45;cursor:not-allowed}
#lbrt-hint{font-size:.7rem;color:#4a6080}
#lbrt-out{padding:.7rem 1rem}
.lbrt-sec{margin-bottom:.65rem;background:#0d1b2a;border:1px solid rgba(232,93,4,.1);border-radius:8px;overflow:hidden}
.lbrt-sec-hd{background:#060f1e;padding:.35rem .7rem;font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#e85d04;border-bottom:1px solid rgba(232,93,4,.1)}
.lbrt-sec-bd{padding:.5rem .7rem;font-size:.8rem;color:#c8d4e0;line-height:1.7;white-space:pre-wrap;word-break:break-word}
.lbrt-li{padding:.25rem 0;display:flex;gap:.5rem;align-items:flex-start;border-bottom:1px solid rgba(255,255,255,.04)}
.lbrt-li:last-child{border:none}
.lbrt-li-dot{color:#e85d04;font-weight:800;flex-shrink:0;margin-top:.04rem}
#lbrt-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:6px;padding:.5rem .8rem;font-size:.78rem;color:#fca5a5;margin:.5rem 1rem;display:none}
#lbrt-spin{color:#4a6080;font-size:.78rem;padding:.6rem 1rem;display:none}
</style>
<div id="lbrt-hdr">
  <h2>&#129504; KI-Lage<span>beurteilung</span></h2>
  <span class="lbrt-bdg">DeepSeek</span>
  <span class="lbrt-bdg">FwDV 100</span>
  <span class="lbrt-bdg">F&uuml;hrungsvorgang</span>
</div>
<div id="lbrt-inp-wrap">
  <div id="lbrt-inp-lbl">Lagebeschreibung eingeben</div>
  <textarea id="lbrt-ta" placeholder="Beschreiben Sie die aktuelle Einsatzlage: Was ist passiert? Wo? Wie viele Betroffene? Welche Gefahren? Eingesetzte Kräfte?&#10;&#10;Beispiel: Brand in einem 4-geschossigen Wohnhaus, 3. OG, 18 Uhr, Rauch im Treppenhaus, 2 Personen vermisst, 1 Trupp PA im Angriff, RTW 1 vor Ort..." maxlength="4000" oninput="document.getElementById('lbrt-char').textContent=this.value.length+'/4000'"></textarea>
  <div id="lbrt-char">0/4000</div>
  <div id="lbrt-sub-row">
    <button id="lbrt-btn" onclick="lbrtSubmit()">&#129504; Lagebeurteilung erstellen</button>
    <span id="lbrt-hint">Antwort in ca. 10&ndash;20 Sekunden</span>
  </div>
</div>
<div id="lbrt-err"></div>
<div id="lbrt-spin">&#9711; KI analysiert die Lage&hellip;</div>
<div id="lbrt-out"></div>
<script>
(function(){
  var NONCE=<?php echo wp_json_encode( $nonce ); ?>;
  var API=<?php echo wp_json_encode( $api_url ); ?>;

  window.lbrtSubmit=function(){
    var ta=document.getElementById('lbrt-ta');
    var lage=ta.value.trim();
    if(lage.length<20){showErr('Bitte die Lage ausführlicher beschreiben.');return;}
    var btn=document.getElementById('lbrt-btn');
    btn.disabled=true;
    document.getElementById('lbrt-err').style.display='none';
    document.getElementById('lbrt-out').innerHTML='';
    document.getElementById('lbrt-spin').style.display='block';

    fetch(API,{
      method:'POST',
      headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
      body:JSON.stringify({lage:lage,_nonce:NONCE})
    })
    .then(function(r){return r.json();})
    .then(function(d){
      btn.disabled=false;
      document.getElementById('lbrt-spin').style.display='none';
      if(d.error){showErr(d.error);return;}
      renderLage(d);
    })
    .catch(function(e){
      btn.disabled=false;
      document.getElementById('lbrt-spin').style.display='none';
      showErr('Netzwerkfehler: '+e.message);
    });
  };

  function showErr(msg){var el=document.getElementById('lbrt-err');el.textContent='⚠️ '+msg;el.style.display='block';}

  function sec(title,content){
    return '<div class="lbrt-sec"><div class="lbrt-sec-hd">'+title+'</div><div class="lbrt-sec-bd">'+esc(content)+'</div></div>';
  }
  function listSec(title,arr){
    if(!Array.isArray(arr)||!arr.length)return'';
    return'<div class="lbrt-sec"><div class="lbrt-sec-hd">'+title+'</div><div class="lbrt-sec-bd">'
      +arr.map(function(i){return'<div class="lbrt-li"><span class="lbrt-li-dot">▶</span><span>'+esc(i)+'</span></div>';}).join('')
      +'</div></div>';
  }
  function esc(s){var d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}

  function renderLage(d){
    var out=document.getElementById('lbrt-out');
    out.innerHTML=
      sec('1. Lagefeststellung',d.lagefeststellung||d.lage||'')
      +sec('2. Lagebeurteilung',d.lagebeurteilung||d.beurteilung||'')
      +sec('3. Führungsentschluss',d.fuehrungsentschluss||d.entschluss||'')
      +sec('4. Auftragserteilung',d.auftragserteilung||d.auftrag||'')
      +listSec('5. Sofortmaßnahmen',d.massnahmen)
      +sec('6. Empfohlene Kräfte & Mittel',d.kraefte||'')
      +sec('7. Risiken & Sicherheitshinweise',d.risiken||'');
  }
})();
</script>
</div>
    <?php
    return ob_get_clean();
} );

// ─── Shortcode: Nachbesprechung ───────────────────────────────────────────────

add_shortcode( 'nachbesprechung_ki', function (): string {
    ob_start();
    $nonce   = wp_create_nonce( 'ptnb_query' );
    $api_url = esc_url( rest_url( 'planungs-tool/v1/nachbesprechung' ) );
    ?>
<div id="nbsp">
<style>
#nbsp *{box-sizing:border-box;font-family:system-ui,sans-serif}
#nbsp{background:#0a1628;color:#f0f4f8;border-radius:10px;overflow:hidden;border:1.5px solid rgba(232,93,4,.25);max-width:860px;margin:0 auto}
#nbsp-hdr{background:#0f0f1a;border-bottom:2px solid #e85d04;padding:.55rem 1rem;display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
#nbsp-hdr h2{font-size:.98rem;font-weight:800;margin:0}#nbsp-hdr h2 span{color:#e85d04}
.nbsp-bdg{font-size:.63rem;background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.1rem .4rem;border-radius:3px}
#nbsp-inp-wrap{padding:.75rem 1rem;background:#0d1b2a;border-bottom:1px solid rgba(232,93,4,.08)}
#nbsp-inp-lbl{font-size:.72rem;font-weight:700;color:#8ba0b4;margin-bottom:.35rem;text-transform:uppercase;letter-spacing:.3px}
#nbsp-ta{width:100%;background:#112240;border:1.5px solid rgba(232,93,4,.2);color:#f0f4f8;border-radius:6px;padding:.5rem .7rem;font-size:.82rem;resize:vertical;min-height:130px;line-height:1.55}
#nbsp-ta:focus{outline:none;border-color:#e85d04}
#nbsp-ta::placeholder{color:#3a5070}
#nbsp-char{font-size:.65rem;color:#2a4060;text-align:right;margin-top:.2rem}
#nbsp-sub-row{display:flex;align-items:center;gap:.7rem;margin-top:.5rem;flex-wrap:wrap}
#nbsp-btn{background:#e85d04;border:none;color:#fff;padding:.35rem 1.1rem;border-radius:5px;cursor:pointer;font-size:.82rem;font-weight:700;transition:background .15s}
#nbsp-btn:hover{background:#f97316}#nbsp-btn:disabled{opacity:.45;cursor:not-allowed}
#nbsp-hint{font-size:.7rem;color:#4a6080}
#nbsp-out{padding:.7rem 1rem}
.nbsp-sec{margin-bottom:.65rem;background:#0d1b2a;border:1px solid rgba(232,93,4,.1);border-radius:8px;overflow:hidden}
.nbsp-sec-hd{background:#060f1e;padding:.35rem .7rem;font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid rgba(232,93,4,.1)}
.nbsp-sec-hd.grn{color:#22c55e}.nbsp-sec-hd.ylw{color:#eab308}.nbsp-sec-hd.blue{color:#60a5fa}.nbsp-sec-hd.ora{color:#e85d04}
.nbsp-sec-bd{padding:.5rem .7rem;font-size:.8rem;color:#c8d4e0;line-height:1.7;white-space:pre-wrap;word-break:break-word}
.nbsp-li{padding:.25rem 0;display:flex;gap:.5rem;align-items:flex-start;border-bottom:1px solid rgba(255,255,255,.04)}
.nbsp-li:last-child{border:none}
.nbsp-li-dot{font-weight:800;flex-shrink:0;margin-top:.04rem}
.nbsp-li.pos .nbsp-li-dot{color:#22c55e}
.nbsp-li.imp .nbsp-li-dot{color:#eab308}
.nbsp-li.emp .nbsp-li-dot{color:#60a5fa}
#nbsp-prot-btn{display:none;margin:.4rem 0 .7rem;background:rgba(232,93,4,.1);border:1px solid rgba(232,93,4,.25);color:#e85d04;padding:.28rem .75rem;border-radius:5px;cursor:pointer;font-size:.75rem;font-weight:700}
#nbsp-prot-btn:hover{background:rgba(232,93,4,.2)}
#nbsp-copied{font-size:.72rem;color:#22c55e;display:none;margin-left:.5rem}
#nbsp-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:6px;padding:.5rem .8rem;font-size:.78rem;color:#fca5a5;margin:.5rem 1rem;display:none}
#nbsp-spin{color:#4a6080;font-size:.78rem;padding:.6rem 1rem;display:none}
</style>
<div id="nbsp-hdr">
  <h2>&#128203; KI-Nach<span>besprechung</span></h2>
  <span class="nbsp-bdg">DeepSeek</span>
  <span class="nbsp-bdg">Debriefing</span>
  <span class="nbsp-bdg">Lessons Learned</span>
</div>
<div id="nbsp-inp-wrap">
  <div id="nbsp-inp-lbl">Einsatznotizen eingeben</div>
  <textarea id="nbsp-ta" placeholder="Beschreiben Sie den Einsatz: Was war der Auftrag? Wie verlief der Einsatz? Welche Probleme gab es? Was hat gut funktioniert?&#10;&#10;Beispiel: Brand im Kellergeschoss, Alarmierung 02:15 Uhr, ersteintreffende Einheit nach 6 min, PA-Suchtrupp hat nach 8 min 1 bewusstlose Person gerettet, Atemschutzüberwachung hatte Schwierigkeiten bei der Zeitkontrolle..." maxlength="6000" oninput="document.getElementById('nbsp-char').textContent=this.value.length+'/6000'"></textarea>
  <div id="nbsp-char">0/6000</div>
  <div id="nbsp-sub-row">
    <button id="nbsp-btn" onclick="nbspSubmit()">&#128203; Nachbesprechung erstellen</button>
    <span id="nbsp-hint">Antwort in ca. 10&ndash;20 Sekunden</span>
    <button id="nbsp-prot-btn" onclick="nbspCopyProt()">&#128203; Protokoll kopieren<span id="nbsp-copied"> &#10003; Kopiert!</span></button>
  </div>
</div>
<div id="nbsp-err"></div>
<div id="nbsp-spin">&#9711; KI erstellt Nachbesprechungsprotokoll&hellip;</div>
<div id="nbsp-out"></div>
<script>
(function(){
  var NONCE=<?php echo wp_json_encode( $nonce ); ?>;
  var API=<?php echo wp_json_encode( $api_url ); ?>;
  var _protokoll='';

  window.nbspSubmit=function(){
    var ta=document.getElementById('nbsp-ta');
    var notizen=ta.value.trim();
    if(notizen.length<30){showErr('Bitte mehr Einsatzdetails eingeben.');return;}
    var btn=document.getElementById('nbsp-btn');
    btn.disabled=true;
    document.getElementById('nbsp-err').style.display='none';
    document.getElementById('nbsp-out').innerHTML='';
    document.getElementById('nbsp-prot-btn').style.display='none';
    document.getElementById('nbsp-spin').style.display='block';

    fetch(API,{
      method:'POST',
      headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
      body:JSON.stringify({notizen:notizen,_nonce:NONCE})
    })
    .then(function(r){return r.json();})
    .then(function(d){
      btn.disabled=false;
      document.getElementById('nbsp-spin').style.display='none';
      if(d.error){showErr(d.error);return;}
      renderNach(d);
    })
    .catch(function(e){
      btn.disabled=false;
      document.getElementById('nbsp-spin').style.display='none';
      showErr('Netzwerkfehler: '+e.message);
    });
  };

  function showErr(msg){var el=document.getElementById('nbsp-err');el.textContent='⚠️ '+msg;el.style.display='block';}
  function esc(s){var d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}

  function listSec(title,arr,cls,dotChar,dotCls){
    if(!Array.isArray(arr)||!arr.length)return'';
    return'<div class="nbsp-sec"><div class="nbsp-sec-hd '+cls+'">'+title+'</div><div class="nbsp-sec-bd">'
      +arr.map(function(i){return'<div class="nbsp-li '+dotCls+'"><span class="nbsp-li-dot">'+dotChar+'</span><span>'+esc(i)+'</span></div>';}).join('')
      +'</div></div>';
  }
  function textSec(title,content,cls){
    return'<div class="nbsp-sec"><div class="nbsp-sec-hd '+cls+'">'+title+'</div><div class="nbsp-sec-bd">'+esc(content)+'</div></div>';
  }

  function renderNach(d){
    _protokoll=d.protokoll||'';
    var out=document.getElementById('nbsp-out');
    out.innerHTML=
      textSec('Einsatzzusammenfassung',d.zusammenfassung||'','ora')
      +textSec('Zeitlinie / Ablauf',d.zeitlinie||'','blue')
      +listSec('Was lief gut?',d.positiv,'grn','✓','pos')
      +listSec('Verbesserungspotenzial',d.verbesserung,'ylw','△','imp')
      +listSec('Empfehlungen',d.empfehlungen,'blue','▶','emp')
      +textSec('Formales Protokoll (für die Akte)',_protokoll,'ora');
    if(_protokoll)document.getElementById('nbsp-prot-btn').style.display='inline-block';
  }

  window.nbspCopyProt=function(){
    if(!_protokoll)return;
    var copied=document.getElementById('nbsp-copied');
    if(navigator.clipboard){
      navigator.clipboard.writeText(_protokoll).then(function(){
        copied.style.display='inline';setTimeout(function(){copied.style.display='none';},2000);
      });
    } else {
      var ta=document.createElement('textarea');ta.value=_protokoll;document.body.appendChild(ta);
      ta.select();document.execCommand('copy');document.body.removeChild(ta);
      copied.style.display='inline';setTimeout(function(){copied.style.display='none';},2000);
    }
  };
})();
</script>
</div>
    <?php
    return ob_get_clean();
} );
