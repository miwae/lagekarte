<?php
/**
 * Plugin Name: Wetter am Einsatzort
 * Description: Echtzeit-Wetterdaten via Open-Meteo (kostenlos, kein API-Key nötig). Shortcode: [wetter_einsatzort]
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'wetter_einsatzort', function (): string {
    ob_start();
    ?>
<div id="wew">
<style>
#wew *{box-sizing:border-box;font-family:system-ui,sans-serif}
#wew{background:#0a1628;color:#f0f4f8;border-radius:10px;overflow:hidden;border:1.5px solid rgba(232,93,4,.25);max-width:860px;margin:0 auto}
#wew-hdr{background:#0f0f1a;border-bottom:2px solid #e85d04;padding:.55rem 1rem;display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
#wew-hdr h2{font-size:.98rem;font-weight:800;margin:0}
#wew-hdr h2 span{color:#e85d04}
.wew-bdg{font-size:.63rem;background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.1rem .4rem;border-radius:3px}
#wew-srch{padding:.6rem 1rem;background:#0d1b2a;border-bottom:1px solid rgba(232,93,4,.1);display:flex;gap:.5rem;flex-wrap:wrap;position:relative}
#wew-inp{flex:1;min-width:180px;background:#112240;border:1.5px solid rgba(232,93,4,.2);color:#f0f4f8;border-radius:5px;padding:.35rem .65rem;font-size:.84rem}
#wew-inp:focus{outline:none;border-color:#e85d04}
#wew-inp::placeholder{color:#3a5070}
#wew-sb{background:#e85d04;border:none;color:#fff;padding:.35rem .9rem;border-radius:5px;cursor:pointer;font-size:.82rem;font-weight:700;white-space:nowrap}
#wew-sb:hover{background:#f97316}
#wew-sb:disabled{opacity:.45;cursor:not-allowed}
#wew-ac{position:absolute;top:100%;left:1rem;right:1rem;background:#112240;border:1.5px solid rgba(232,93,4,.3);border-radius:0 0 6px 6px;z-index:200;display:none;max-height:160px;overflow-y:auto}
.wew-ac-i{padding:.4rem .7rem;font-size:.78rem;cursor:pointer;color:#c8d4e0;border-bottom:1px solid rgba(255,255,255,.04)}
.wew-ac-i:hover{background:rgba(232,93,4,.1);color:#e85d04}
#wew-body{padding:.8rem 1rem}
#wew-loc-nm{font-size:.88rem;font-weight:700;color:#e85d04;margin-bottom:.45rem}
#wew-spin{color:#4a6080;font-size:.8rem;padding:1rem 0}
#wew-empty{color:#3a5070;font-size:.8rem;text-align:center;padding:2rem 1rem;line-height:1.8}
#wew-cur{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.5rem;margin-bottom:.7rem}
.we-tile{background:#0d1b2a;border:1px solid rgba(232,93,4,.12);border-radius:7px;padding:.5rem .6rem}
.we-tl{font-size:.6rem;color:#4a6080;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.15rem;font-weight:700}
.we-tv{font-size:1.1rem;font-weight:800;color:#f0f4f8}
.we-tu{font-size:.63rem;color:#8ba0b4;margin-left:.1rem}
.we-ts{font-size:.65rem;color:#8ba0b4;margin-top:.1rem}
#we-wind-tile{grid-column:span 2}
.we-warn{background:rgba(239,68,68,.07);border-color:rgba(239,68,68,.25)!important}
.we-warn .we-tl{color:#ef4444}
#wew-fc{margin-top:.4rem}
#wew-fc-hd{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#4a6080;margin-bottom:.35rem}
#wew-fc-tbl{width:100%;border-collapse:collapse;font-size:.73rem}
#wew-fc-tbl th{color:#4a6080;font-weight:700;text-align:left;padding:.22rem .4rem;border-bottom:1px solid rgba(232,93,4,.1)}
#wew-fc-tbl td{padding:.26rem .4rem;border-bottom:1px solid rgba(255,255,255,.04);color:#c8d4e0}
#wew-fc-tbl tr.wew-now td{background:rgba(232,93,4,.07);color:#f0f4f8;font-weight:700}
#wew-abc{background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.2);border-radius:6px;padding:.45rem .6rem;margin-top:.5rem;font-size:.73rem;color:#fca5a5;line-height:1.6;display:none}
#wew-abc strong{color:#ef4444}
#wew-upd{font-size:.65rem;color:#2a4060;padding:.35rem 1rem;border-top:1px solid rgba(232,93,4,.06)}
</style>
<div id="wew-hdr">
  <h2>&#9925; Wetter am <span>Einsatzort</span></h2>
  <span class="wew-bdg">Open-Meteo API</span>
  <span class="wew-bdg">ABC-Lage &middot; Windinfo</span>
  <span class="wew-bdg">Kein API-Key</span>
</div>
<div id="wew-srch">
  <input id="wew-inp" placeholder="&#128205; Ort, PLZ oder Koordinaten (52.1, 7.2)&hellip;"
    autocomplete="off" oninput="wewAC(this.value)"
    onkeydown="if(event.key==='Enter'){document.getElementById('wew-ac').style.display='none';wewSrch();}">
  <button id="wew-sb" onclick="wewSrch()">&#9728; Wetter abrufen</button>
  <div id="wew-ac"></div>
</div>
<div id="wew-body">
  <div id="wew-empty">&#127757; Ort eingeben und Wetter abrufen.<br><small>Kostenlos &middot; datenschutzfreundlich &middot; keine Registrierung n&ouml;tig.</small></div>
  <div id="wew-spin" style="display:none">&#9711; Wetterdaten werden abgerufen&hellip;</div>
  <div id="wew-loc-nm" style="display:none"></div>
  <div id="wew-cur" style="display:none">
    <div class="we-tile">
      <div class="we-tl">Temperatur</div>
      <div class="we-tv" id="we-temp">—<span class="we-tu">°C</span></div>
      <div class="we-ts" id="we-feels">gef&uuml;hlt — °C</div>
    </div>
    <div class="we-tile">
      <div class="we-tl">Wetterlage</div>
      <div class="we-tv" id="we-code" style="font-size:.82rem">—</div>
      <div class="we-ts" id="we-cloud">Bedeckung —%</div>
    </div>
    <div class="we-tile we-warn" id="we-wind-tile">
      <div class="we-tl">&#9888; Wind (ABC-kritisch)</div>
      <div style="display:flex;align-items:center;gap:.4rem">
        <span id="we-arr" style="font-size:1.4rem;display:inline-block;transition:transform .5s">&#8593;</span>
        <span class="we-tv" id="we-wspd">—</span><span class="we-tu">km/h</span>
        <span style="font-size:.82rem;font-weight:800;color:#ef4444;margin-left:.2rem" id="we-wdir">—</span>
      </div>
      <div class="we-ts" id="we-gusts">B&ouml;en: — km/h</div>
    </div>
    <div class="we-tile">
      <div class="we-tl">Niederschlag</div>
      <div class="we-tv" id="we-prec">—<span class="we-tu">mm</span></div>
      <div class="we-ts">Aktuell (letzte Std.)</div>
    </div>
    <div class="we-tile">
      <div class="we-tl">Luftfeuchtigkeit</div>
      <div class="we-tv" id="we-hum">—<span class="we-tu">%</span></div>
    </div>
  </div>
  <div id="wew-abc"></div>
  <div id="wew-fc" style="display:none">
    <div id="wew-fc-hd">N&auml;chste 12 Stunden</div>
    <table id="wew-fc-tbl">
      <thead><tr>
        <th>Zeit</th><th>Temp</th><th>Wind km/h</th><th>Richtung</th><th>Nied.&shy;schlag %</th>
      </tr></thead>
      <tbody id="wew-fc-tb"></tbody>
    </table>
  </div>
</div>
<div id="wew-upd"></div>
<script>
(function(){
  var WMO={
    0:'☀️ Klarer Himmel',1:'🌤 Überwiegend klar',2:'⛅ Teils bewölkt',3:'☁️ Bedeckt',
    45:'🌫 Nebel',48:'🌫 Gefrierender Nebel',
    51:'🌦 Leichter Sprühregen',53:'🌦 Sprühregen',55:'🌧 Starker Sprühregen',
    61:'🌧 Leichter Regen',63:'🌧 Mäßiger Regen',65:'🌧 Starker Regen',
    71:'🌨 Leichter Schneefall',73:'🌨 Schneefall',75:'❄️ Starker Schneefall',
    80:'🌦 Leichte Regenschauer',81:'🌧 Regenschauer',82:'⛈ Heftige Schauer',
    85:'🌨 Schneeschauer',86:'🌨 Heftige Schneeschauer',
    95:'⛈ Gewitter',96:'⛈ Gewitter mit Hagel',99:'⛈ Schweres Gewitter'
  };
  var DIRS=['N','NO','O','SO','S','SW','W','NW'];
  function wDir(d){return DIRS[Math.round(d/45)%8];}
  var acTmr;

  window.wewAC=function(v){
    clearTimeout(acTmr);
    var ac=document.getElementById('wew-ac');
    if(!v||v.length<2){ac.style.display='none';return;}
    if(/^-?\d+\.?\d*\s*,\s*-?\d+\.?\d*$/.test(v)){ac.style.display='none';return;}
    acTmr=setTimeout(function(){
      fetch('https://nominatim.openstreetmap.org/search?q='+encodeURIComponent(v)+'&format=json&limit=5&accept-language=de&countrycodes=de,at,ch')
      .then(function(r){return r.json();})
      .then(function(d){
        ac.innerHTML='';
        if(!d.length){ac.style.display='none';return;}
        d.forEach(function(item){
          var el=document.createElement('div');
          el.className='wew-ac-i';
          el.textContent=item.display_name;
          el.onclick=function(){
            document.getElementById('wew-inp').value=item.display_name.split(',').slice(0,3).join(',');
            ac.style.display='none';
            wewLoad(parseFloat(item.lat),parseFloat(item.lon),item.display_name.split(',')[0]);
          };
          ac.appendChild(el);
        });
        ac.style.display='block';
      }).catch(function(){ac.style.display='none';});
    },500);
  };

  window.wewSrch=function(){
    document.getElementById('wew-ac').style.display='none';
    var v=document.getElementById('wew-inp').value.trim();
    if(!v)return;
    var btn=document.getElementById('wew-sb');btn.disabled=true;
    var m=v.match(/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/);
    if(m){wewLoad(parseFloat(m[1]),parseFloat(m[2]),'Koordinaten '+m[1]+', '+m[2]);btn.disabled=false;return;}
    fetch('https://nominatim.openstreetmap.org/search?q='+encodeURIComponent(v)+'&format=json&limit=1&accept-language=de')
    .then(function(r){return r.json();})
    .then(function(d){
      btn.disabled=false;
      if(!d.length){alert('Ort nicht gefunden.');return;}
      wewLoad(parseFloat(d[0].lat),parseFloat(d[0].lon),d[0].display_name.split(',')[0]);
    }).catch(function(){btn.disabled=false;alert('Netzwerkfehler bei Geocodierung.');});
  };

  function wewLoad(lat,lon,name){
    ['wew-empty','wew-cur','wew-fc','wew-abc'].forEach(function(id){
      var el=document.getElementById(id);if(el)el.style.display='none';
    });
    document.getElementById('wew-spin').style.display='block';
    document.getElementById('wew-loc-nm').style.display='block';
    document.getElementById('wew-loc-nm').textContent='📍 '+name;

    var url='https://api.open-meteo.com/v1/forecast?latitude='+lat+'&longitude='+lon
      +'&current=temperature_2m,apparent_temperature,relative_humidity_2m,precipitation,weather_code,cloud_cover,wind_speed_10m,wind_direction_10m,wind_gusts_10m'
      +'&hourly=temperature_2m,precipitation_probability,wind_speed_10m,wind_direction_10m'
      +'&forecast_days=1&timezone=auto';

    fetch(url)
    .then(function(r){return r.json();})
    .then(function(data){
      document.getElementById('wew-spin').style.display='none';
      var c=data.current;

      document.getElementById('we-temp').innerHTML=c.temperature_2m+'<span class="we-tu">°C</span>';
      document.getElementById('we-feels').textContent='gefühlt '+c.apparent_temperature+' °C';
      document.getElementById('we-code').textContent=WMO[c.weather_code]||('Code '+c.weather_code);
      document.getElementById('we-cloud').textContent='Bedeckung '+c.cloud_cover+'%';

      var ws=Math.round(c.wind_speed_10m);
      var wd=wDir(c.wind_direction_10m);
      document.getElementById('we-wspd').textContent=ws;
      document.getElementById('we-wdir').textContent=wd;
      document.getElementById('we-arr').style.transform='rotate('+c.wind_direction_10m+'deg)';
      document.getElementById('we-gusts').textContent='Böen: '+Math.round(c.wind_gusts_10m)+' km/h';

      document.getElementById('we-prec').innerHTML=c.precipitation+'<span class="we-tu">mm</span>';
      document.getElementById('we-hum').innerHTML=c.relative_humidity_2m+'<span class="we-tu">%</span>';

      // ABC-Lage-Hinweis bei erhöhtem Wind
      var abcEl=document.getElementById('wew-abc');
      if(ws>=15){
        abcEl.innerHTML='<strong>⚠️ Windhinweis für ABC-Lagen:</strong> Wind '+ws+' km/h aus <strong>'+wd+'</strong> '
          +'– Ausbreitungsrichtung von Gefahrstoffen/Rauch beachten! Einsatzkräfte <strong>windabgewandt</strong> aufstellen.';
        abcEl.style.display='block';
      }

      // Stündliche Vorhersage
      var h=data.hourly;
      var ci=new Date(c.time).getHours();
      var tb=document.getElementById('wew-fc-tb');
      tb.innerHTML='';
      for(var i=ci;i<Math.min(ci+13,24);i++){
        var tr=document.createElement('tr');
        if(i===ci)tr.className='wew-now';
        var t=new Date(h.time[i]);
        tr.innerHTML='<td>'+t.toLocaleTimeString('de-DE',{hour:'2-digit',minute:'2-digit'})+'</td>'
          +'<td>'+h.temperature_2m[i]+'°C</td>'
          +'<td>'+Math.round(h.wind_speed_10m[i])+'</td>'
          +'<td>'+wDir(h.wind_direction_10m[i])+'</td>'
          +'<td>'+h.precipitation_probability[i]+'%</td>';
        tb.appendChild(tr);
      }
      document.getElementById('wew-cur').style.display='grid';
      document.getElementById('wew-fc').style.display='block';
      document.getElementById('wew-upd').textContent='Aktualisiert: '+new Date().toLocaleString('de-DE');
    })
    .catch(function(){
      document.getElementById('wew-spin').style.display='none';
      document.getElementById('wew-empty').style.display='block';
      document.getElementById('wew-empty').textContent='❌ Fehler beim Abrufen der Wetterdaten.';
    });
  }

  document.addEventListener('click',function(e){
    if(!e.target.closest('#wew-srch'))document.getElementById('wew-ac').style.display='none';
  });
})();
</script>
</div>
    <?php
    return ob_get_clean();
} );
