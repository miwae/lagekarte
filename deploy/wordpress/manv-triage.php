<?php
/**
 * Plugin Name: MANV-Triage-Übersicht
 * Description: Sichtungskategorien-Zähler für Massenanfall von Verletzten (MANV). Shortcode: [manv_triage]
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'manv_triage', function (): string {
    ob_start();
    ?>
<div id="manvw">
<style>
#manvw *{box-sizing:border-box;font-family:system-ui,sans-serif}
#manvw{background:#0a1628;color:#f0f4f8;border-radius:10px;overflow:hidden;border:1.5px solid rgba(232,93,4,.25);max-width:860px;margin:0 auto}
#manvw-hdr{background:#0f0f1a;border-bottom:2px solid #e85d04;padding:.55rem 1rem;display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
#manvw-hdr h2{font-size:.98rem;font-weight:800;margin:0}
#manvw-hdr h2 span{color:#e85d04}
.mv-bdg{font-size:.63rem;background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.1rem .4rem;border-radius:3px}
#manvw-meta{padding:.55rem 1rem;background:#0d1b2a;border-bottom:1px solid rgba(232,93,4,.1);display:flex;gap:.5rem;flex-wrap:wrap}
.mv-inp{background:#112240;border:1.5px solid rgba(232,93,4,.2);color:#f0f4f8;border-radius:5px;padding:.3rem .55rem;font-size:.8rem;flex:1;min-width:130px}
.mv-inp:focus{outline:none;border-color:#e85d04}
.mv-inp::placeholder{color:#3a5070}
#manvw-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:.6rem;padding:.8rem 1rem}
.sk{border-radius:10px;padding:.7rem .5rem;display:flex;flex-direction:column;align-items:center;gap:.3rem;border:2px solid transparent;transition:transform .1s}
.sk:hover{transform:translateY(-1px)}
.sk1{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.45)}
.sk2{background:rgba(234,179,8,.1);border-color:rgba(234,179,8,.45)}
.sk3{background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.45)}
.sk4{background:rgba(99,102,241,.1);border-color:rgba(99,102,241,.45)}
.skt{background:rgba(100,116,139,.1);border-color:rgba(100,116,139,.45)}
.sk-lbl{font-size:.63rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px}
.sk1 .sk-lbl{color:#ef4444}.sk2 .sk-lbl{color:#eab308}.sk3 .sk-lbl{color:#22c55e}
.sk4 .sk-lbl{color:#818cf8}.skt .sk-lbl{color:#94a3b8}
.sk-dsc{font-size:.58rem;color:#8ba0b4;text-align:center;line-height:1.35}
.sk-num{font-size:2.8rem;font-weight:900;line-height:1;font-variant-numeric:tabular-nums}
.sk1 .sk-num{color:#ef4444}.sk2 .sk-num{color:#eab308}.sk3 .sk-num{color:#22c55e}
.sk4 .sk-num{color:#818cf8}.skt .sk-num{color:#94a3b8}
.sk-bts{display:flex;gap:.35rem}
.sk-bt{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:#f0f4f8;width:36px;height:36px;border-radius:7px;cursor:pointer;font-size:1.2rem;font-weight:700;display:flex;align-items:center;justify-content:center;transition:background .12s;user-select:none}
.sk-bt:hover{background:rgba(255,255,255,.14)}
#manvw-bar{padding:.5rem 1rem;background:#0d1b2a;border-top:1px solid rgba(232,93,4,.1);display:flex;align-items:center;gap:1rem;flex-wrap:wrap}
#mv-tot-n{font-size:1.5rem;font-weight:900;color:#e85d04}
#mv-tot-l{font-size:.72rem;color:#8ba0b4}
#manvw-txts{flex:1;min-width:200px;background:#112240;border:1.5px solid rgba(232,93,4,.2);color:#f0f4f8;border-radius:5px;padding:.3rem .55rem;font-size:.78rem;min-height:38px;resize:vertical}
#manvw-txts:focus{outline:none;border-color:#e85d04}
#manvw-txts::placeholder{color:#3a5070}
#manvw-ftr{padding:.45rem 1rem;border-top:1px solid rgba(232,93,4,.08);display:flex;gap:.45rem;align-items:center;flex-wrap:wrap}
.mv-btn{background:#e85d04;border:none;color:#fff;padding:.3rem .85rem;border-radius:5px;cursor:pointer;font-size:.78rem;font-weight:700;transition:background .15s}
.mv-btn:hover{background:#f97316}
.mv-btn.s{background:rgba(232,93,4,.12);color:#e85d04;border:1px solid rgba(232,93,4,.3)}
.mv-btn.s:hover{background:rgba(232,93,4,.2)}
#mv-ts{font-size:.65rem;color:#2a4060;margin-left:auto}
#manvw-trsp{background:#0d1b2a;border-top:1px solid rgba(232,93,4,.08);padding:.45rem 1rem;display:flex;align-items:center;gap:.8rem;flex-wrap:wrap}
.mv-trsp-lbl{font-size:.72rem;color:#8ba0b4}
.mv-trsp-n{font-size:1.1rem;font-weight:800;color:#22c55e}
.mv-trsp-bts{display:flex;gap:.3rem}
@media print{
  body>*:not(#manvw){display:none!important}
  #manvw{border:none;background:#fff!important;color:#000!important;max-width:100%;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  #manvw-ftr{display:none!important}
  .sk{border:2px solid #ccc!important;background:#f5f5f5!important;break-inside:avoid}
  .sk-num,.sk-lbl{color:#000!important}
}
</style>
<div id="manvw-hdr">
  <h2>&#9763;&#65039; MANV-<span>Triage</span></h2>
  <span class="mv-bdg">DIN EN ISO 22315</span>
  <span class="mv-bdg">SK I &ndash; IV &middot; T</span>
</div>
<div id="manvw-meta">
  <input class="mv-inp" id="mv-ev"  placeholder="Ereignis / Einsatz&hellip;">
  <input class="mv-inp" id="mv-loc" placeholder="Ort / BHP-Bezeichnung&hellip;">
  <input class="mv-inp" id="mv-bl"  placeholder="BHP-Leiter&hellip;">
</div>
<div id="manvw-grid">
  <div class="sk sk1">
    <div class="sk-lbl">SK I &mdash; Rot</div>
    <div class="sk-dsc">Sofortige Behandlung<br>Vital bedroht</div>
    <div class="sk-num" id="nsk1">0</div>
    <div class="sk-bts">
      <button class="sk-bt" onclick="mAdj('sk1',-1)">&#8722;</button>
      <button class="sk-bt" onclick="mAdj('sk1',1)">+</button>
    </div>
  </div>
  <div class="sk sk2">
    <div class="sk-lbl">SK II &mdash; Gelb</div>
    <div class="sk-dsc">Aufgeschobene Behandlung<br>Schwer verletzt</div>
    <div class="sk-num" id="nsk2">0</div>
    <div class="sk-bts">
      <button class="sk-bt" onclick="mAdj('sk2',-1)">&#8722;</button>
      <button class="sk-bt" onclick="mAdj('sk2',1)">+</button>
    </div>
  </div>
  <div class="sk sk3">
    <div class="sk-lbl">SK III &mdash; Gr&uuml;n</div>
    <div class="sk-dsc">Leichte Verletzung<br>Ambulant behandelbar</div>
    <div class="sk-num" id="nsk3">0</div>
    <div class="sk-bts">
      <button class="sk-bt" onclick="mAdj('sk3',-1)">&#8722;</button>
      <button class="sk-bt" onclick="mAdj('sk3',1)">+</button>
    </div>
  </div>
  <div class="sk sk4">
    <div class="sk-lbl">SK IV &mdash; Blau</div>
    <div class="sk-dsc">Behandlung aussichtslos<br>Palliativbetreuung</div>
    <div class="sk-num" id="nsk4">0</div>
    <div class="sk-bts">
      <button class="sk-bt" onclick="mAdj('sk4',-1)">&#8722;</button>
      <button class="sk-bt" onclick="mAdj('sk4',1)">+</button>
    </div>
  </div>
  <div class="sk skt">
    <div class="sk-lbl">T &mdash; Schwarz</div>
    <div class="sk-dsc">Verstorben</div>
    <div class="sk-num" id="nskt">0</div>
    <div class="sk-bts">
      <button class="sk-bt" onclick="mAdj('skt',-1)">&#8722;</button>
      <button class="sk-bt" onclick="mAdj('skt',1)">+</button>
    </div>
  </div>
</div>
<div id="manvw-trsp">
  <span class="mv-trsp-lbl">Abtransportiert:</span>
  <span class="mv-trsp-n" id="ntrsp">0</span>
  <div class="mv-trsp-bts">
    <button class="sk-bt" onclick="mAdj('trsp',-1)" style="width:28px;height:28px;font-size:1rem">&#8722;</button>
    <button class="sk-bt" onclick="mAdj('trsp',1)"  style="width:28px;height:28px;font-size:1rem">+</button>
  </div>
  <span class="mv-trsp-lbl" style="margin-left:1rem">Verbleibend: <strong id="nverbl" style="color:#e85d04">0</strong></span>
</div>
<div id="manvw-bar">
  <div><div id="mv-tot-n">0</div><div id="mv-tot-l">Betroffene gesamt (ohne T)</div></div>
  <textarea id="manvw-txts" placeholder="Notizen / Ma&#223;nahmen / Anforderungen&hellip;"></textarea>
</div>
<div id="manvw-ftr">
  <button class="mv-btn" onclick="window.print()">&#128438; Drucken</button>
  <button class="mv-btn s" onclick="mReset()">&#8635; Zur&uuml;cksetzen</button>
  <span id="mv-ts"></span>
</div>
</div>
<script>
(function(){
  var C={sk1:0,sk2:0,sk3:0,sk4:0,skt:0,trsp:0};
  var FLD=['mv-ev','mv-loc','mv-bl','manvw-txts'];
  function render(){
    ['sk1','sk2','sk3','sk4','skt','trsp'].forEach(function(k){
      var el=document.getElementById('n'+k);
      if(el)el.textContent=C[k];
    });
    var living=C.sk1+C.sk2+C.sk3+C.sk4;
    document.getElementById('mv-tot-n').textContent=living+C.skt;
    document.getElementById('nverbl').textContent=Math.max(0,living-C.trsp);
    document.getElementById('mv-ts').textContent='Stand: '+new Date().toLocaleTimeString('de-DE');
  }
  function save(){
    var d={C:C};
    FLD.forEach(function(id){d[id]=(document.getElementById(id)||{}).value||'';});
    localStorage.setItem('pt_manv',JSON.stringify(d));
  }
  function load(){
    try{
      var d=JSON.parse(localStorage.getItem('pt_manv')||'{}');
      if(d.C)Object.assign(C,d.C);
      FLD.forEach(function(id){var el=document.getElementById(id);if(el&&d[id])el.value=d[id];});
    }catch(e){}
    render();
  }
  window.mAdj=function(k,v){C[k]=Math.max(0,(C[k]||0)+v);render();save();};
  window.mReset=function(){
    if(!confirm('Alle Z\u00e4hler zur\u00fccksetzen?'))return;
    C={sk1:0,sk2:0,sk3:0,sk4:0,skt:0,trsp:0};render();save();
  };
  FLD.forEach(function(id){
    var el=document.getElementById(id);
    if(el)el.addEventListener('input',save);
  });
  load();
})();
</script>
    <?php
    return ob_get_clean();
} );
