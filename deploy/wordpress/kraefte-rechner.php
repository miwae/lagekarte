<?php
/**
 * Plugin Name: Kräftestärke-Rechner
 * Description: BOS-Stärkemeldung und Kräfteübersicht mit dynamischer Tabelle. Shortcode: [kraefte_rechner]
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'kraefte_rechner', function (): string {
    ob_start();
    ?>
<div id="krt">
<style>
#krt *{box-sizing:border-box;font-family:system-ui,sans-serif}
#krt{background:#0a1628;color:#f0f4f8;border-radius:10px;overflow:hidden;border:1.5px solid rgba(232,93,4,.25);max-width:900px;margin:0 auto}
#krt-hdr{background:#0f0f1a;border-bottom:2px solid #e85d04;padding:.55rem 1rem;display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
#krt-hdr h2{font-size:.98rem;font-weight:800;margin:0}#krt-hdr h2 span{color:#e85d04}
.krt-bdg{font-size:.63rem;background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.1rem .4rem;border-radius:3px}
#krt-meta{padding:.55rem 1rem;background:#0d1b2a;border-bottom:1px solid rgba(232,93,4,.1);display:flex;gap:.5rem;flex-wrap:wrap}
.krt-mi{background:#112240;border:1.5px solid rgba(232,93,4,.2);color:#f0f4f8;border-radius:5px;padding:.3rem .55rem;font-size:.8rem;flex:1;min-width:140px}
.krt-mi:focus{outline:none;border-color:#e85d04}
.krt-mi::placeholder{color:#3a5070}
#krt-tbl-wrap{overflow-x:auto;padding:.6rem 1rem 0}
#krt-tbl{width:100%;border-collapse:collapse;font-size:.8rem;min-width:560px}
#krt-tbl thead th{background:#0d1b2a;color:#8ba0b4;font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;padding:.35rem .45rem;border-bottom:1.5px solid rgba(232,93,4,.18);text-align:left;white-space:nowrap}
#krt-tbl thead th[title]{cursor:help}
#krt-tbl tbody tr:hover td{background:rgba(232,93,4,.04)}
#krt-tbl td{padding:.28rem .3rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
#krt-tbl tfoot td{background:#0d1b2a;font-weight:800;padding:.35rem .45rem;border-top:1.5px solid rgba(232,93,4,.18)}
#krt-tbl tfoot .krt-sum-lbl{color:#8ba0b4;font-size:.72rem}
#krt-tbl tfoot .krt-sum-n{color:#e85d04;font-size:1rem}
.krt-sel{background:#112240;border:1px solid rgba(232,93,4,.2);color:#f0f4f8;border-radius:4px;padding:.22rem .3rem;font-size:.75rem;width:100%}
.krt-sel:focus{outline:none;border-color:#e85d04}
.krt-inp{background:#112240;border:1px solid rgba(232,93,4,.15);color:#f0f4f8;border-radius:4px;padding:.22rem .4rem;font-size:.75rem;width:100%}
.krt-inp:focus{outline:none;border-color:#e85d04}
.krt-inp::placeholder{color:#3a5070}
.krt-num{background:#112240;border:1px solid rgba(232,93,4,.15);color:#f0f4f8;border-radius:4px;padding:.22rem .3rem;font-size:.82rem;width:52px;text-align:center;font-weight:700}
.krt-num:focus{outline:none;border-color:#e85d04}
.krt-del{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#ef4444;border-radius:4px;width:26px;height:26px;cursor:pointer;font-size:.85rem;display:flex;align-items:center;justify-content:center;transition:background .12s}
.krt-del:hover{background:rgba(239,68,68,.2)}
#krt-act{padding:.55rem 1rem;border-top:1px solid rgba(232,93,4,.08);display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
.krt-add{background:#e85d04;border:none;color:#fff;padding:.3rem .85rem;border-radius:5px;cursor:pointer;font-size:.8rem;font-weight:700;transition:background .15s}
.krt-add:hover{background:#f97316}
#krt-bos-box{background:#0d1b2a;border:1px solid rgba(232,93,4,.2);border-radius:6px;padding:.3rem .65rem;font-size:.78rem;color:#8ba0b4;margin-left:auto}
#krt-bos-val{font-size:1rem;font-weight:900;color:#e85d04}
#krt-ftr{padding:.45rem 1rem;border-top:1px solid rgba(232,93,4,.06);display:flex;gap:.45rem;align-items:center;flex-wrap:wrap}
.krt-btn{background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.3rem .8rem;border-radius:5px;cursor:pointer;font-size:.78rem;font-weight:700;transition:background .12s}
.krt-btn:hover{background:rgba(232,93,4,.22)}
.krt-btn.pr{background:#e85d04;border:none;color:#fff}
.krt-btn.pr:hover{background:#f97316}
.krt-org-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:4px}
#krt-ts{font-size:.65rem;color:#2a4060;margin-left:auto}
@media print{
  body>*{display:none!important}
  #krt{display:block!important;border:none;background:#fff!important;color:#000!important;max-width:100%;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  #krt-ftr,#krt-act .krt-add{display:none!important}
  #krt-tbl td,#krt-tbl th{border:1px solid #ccc!important;background:#fff!important;color:#000!important}
  .krt-del,.krt-num,.krt-inp,.krt-sel{border:1px solid #ccc!important;background:#f5f5f5!important;color:#000!important}
  #krt-tbl tfoot td{background:#f0f0f0!important}
}
</style>
<div id="krt-hdr">
  <h2>&#128106; Kr&auml;ftest&auml;rke-<span>Rechner</span></h2>
  <span class="krt-bdg">BOS St&auml;rkemeldung</span>
  <span class="krt-bdg">F/U/M-Notation</span>
</div>
<div id="krt-meta">
  <input class="krt-mi" id="krt-ev" placeholder="Einsatz / Ereignis...">
  <input class="krt-mi" id="krt-ort" placeholder="Einsatzort...">
  <input class="krt-mi" id="krt-el" placeholder="Einsatzleiter...">
  <input class="krt-mi" type="datetime-local" id="krt-dt">
</div>
<div id="krt-tbl-wrap">
  <table id="krt-tbl">
    <thead>
      <tr>
        <th style="width:80px">Org</th>
        <th>Bezeichnung / Kennung</th>
        <th title="Führungskräfte" style="width:52px">F</th>
        <th title="Unterführer" style="width:52px">U</th>
        <th title="Mannschaft / Helfer" style="width:52px">M</th>
        <th title="Kraftfahrzeuge" style="width:52px">Kfz</th>
        <th style="width:32px"></th>
      </tr>
    </thead>
    <tbody id="krt-tbody"></tbody>
    <tfoot>
      <tr>
        <td colspan="2" class="krt-sum-lbl">Gesamt</td>
        <td><span id="krt-sf" class="krt-sum-n">0</span></td>
        <td><span id="krt-su" class="krt-sum-n">0</span></td>
        <td><span id="krt-sm" class="krt-sum-n">0</span></td>
        <td><span id="krt-sk" class="krt-sum-n">0</span></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
</div>
<div id="krt-act">
  <button class="krt-add" onclick="krtAdd()">+ Einheit hinzuf&uuml;gen</button>
  <div id="krt-bos-box">
    St&auml;rke&nbsp;<span id="krt-bos-val">0/0/0&nbsp;=&nbsp;0</span>
    &nbsp;&nbsp;Kfz&nbsp;<span id="krt-bos-kfz" style="color:#e85d04;font-weight:800">0</span>
  </div>
</div>
<div id="krt-ftr">
  <button class="krt-btn pr" onclick="window.print()">&#128438; Drucken</button>
  <button class="krt-btn" onclick="krtReset()">&#8635; Leeren</button>
  <span id="krt-ts"></span>
</div>
<script>
(function(){
  var rows=[],rid=0;
  var ORGS=['FW','THW','RD','HiOrg','Pol','BW','Sonst'];
  var ORG_C={FW:'#e85d04',THW:'#3b82f6',RD:'#ef4444',HiOrg:'#22c55e',Pol:'#6366f1',BW:'#78716c',Sonst:'#94a3b8'};
  var META=['krt-ev','krt-ort','krt-el','krt-dt'];

  function esc(s){var d=document.createElement('div');d.textContent=String(s);return d.innerHTML;}

  window.krtAdd=function(org,name,f,u,m,kfz){
    rows.push({id:++rid,org:org||'FW',name:name||'',f:+f||0,u:+u||0,m:+m||0,kfz:+kfz||0});
    render();save();
  };
  window.krtDel=function(id){rows=rows.filter(function(r){return r.id!==id;});render();save();};
  window.krtUpd=function(id,field,value){
    var r=rows.find(function(r){return r.id===id;});
    if(r){r[field]=(field==='org'||field==='name')?value:(parseInt(value,10)||0);calc();save();}
  };

  function calc(){
    var sf=0,su=0,sm=0,sk=0;
    rows.forEach(function(r){sf+=r.f;su+=r.u;sm+=r.m;sk+=r.kfz;});
    document.getElementById('krt-sf').textContent=sf;
    document.getElementById('krt-su').textContent=su;
    document.getElementById('krt-sm').textContent=sm;
    document.getElementById('krt-sk').textContent=sk;
    document.getElementById('krt-bos-val').textContent=sf+'/'+su+'/'+sm+'\u00a0=\u00a0'+(sf+su+sm);
    document.getElementById('krt-bos-kfz').textContent=sk;
  }

  function render(){
    var tb=document.getElementById('krt-tbody');
    tb.innerHTML='';
    rows.forEach(function(r){
      var tr=document.createElement('tr');
      var selOpts=ORGS.map(function(o){
        return '<option value="'+o+'"'+(r.org===o?' selected':'')+'>'+(o==='Sonst'?'Sonstiges':o)+'</option>';
      }).join('');
      tr.innerHTML=
        '<td><select class="krt-sel" onchange="krtUpd('+r.id+',\'org\',this.value)">'
          +'<option value="" disabled></option>'+selOpts+'</select></td>'
        +'<td><input class="krt-inp" value="'+esc(r.name)+'" oninput="krtUpd('+r.id+',\'name\',this.value)" placeholder="Bezeichnung / Kennung..."></td>'
        +['f','u','m','kfz'].map(function(ff){
          return '<td><input class="krt-num" type="number" min="0" value="'+r[ff]+'" oninput="krtUpd('+r.id+',\''+ff+'\',this.value)"></td>';
        }).join('')
        +'<td><button class="krt-del" onclick="krtDel('+r.id+')" title="Zeile entfernen">&times;</button></td>';
      tb.appendChild(tr);
    });
    calc();
    document.getElementById('krt-ts').textContent='Aktualisiert '+new Date().toLocaleTimeString('de-DE');
  }

  function save(){
    var d={rows:rows,rid:rid};
    META.forEach(function(id){d[id]=(document.getElementById(id)||{}).value||'';});
    localStorage.setItem('pt_kraefte',JSON.stringify(d));
  }

  function load(){
    try{
      var d=JSON.parse(localStorage.getItem('pt_kraefte')||'{}');
      if(d.rows){rows=d.rows;rid=d.rid||0;}
      META.forEach(function(id){if(d[id]){var el=document.getElementById(id);if(el)el.value=d[id];}});
    }catch(e){}
    render();
    if(!rows.length)krtAdd();
  }

  window.krtReset=function(){
    if(!confirm('Alle Eintr\u00e4ge l\u00f6schen?'))return;
    rows=[];rid=0;render();save();krtAdd();
  };

  META.forEach(function(id){
    var el=document.getElementById(id);if(el)el.addEventListener('input',save);
  });

  load();
})();
</script>
</div>
    <?php
    return ob_get_clean();
} );
