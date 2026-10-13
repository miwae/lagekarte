<?php
/**
 * Plugin Name: Schichtplanung
 * Description: Einsatz-Schichtplanung mit Personalverwaltung und Druckfunktion. Shortcode: [schichtplanung]
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'schichtplanung', function (): string {
    ob_start();
    ?>
<div id="schp">
<style>
#schp *{box-sizing:border-box;font-family:system-ui,sans-serif}
#schp{background:#0a1628;color:#f0f4f8;border-radius:10px;overflow:hidden;border:1.5px solid rgba(232,93,4,.25);max-width:950px;margin:0 auto}
#schp-hdr{background:#0f0f1a;border-bottom:2px solid #e85d04;padding:.55rem 1rem;display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
#schp-hdr h2{font-size:.98rem;font-weight:800;margin:0}#schp-hdr h2 span{color:#e85d04}
.schp-bdg{font-size:.63rem;background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.1rem .4rem;border-radius:3px}
#schp-cfg{padding:.6rem 1rem;background:#0d1b2a;border-bottom:1px solid rgba(232,93,4,.1);display:flex;gap:.6rem;flex-wrap:wrap;align-items:flex-end}
.schp-fg{display:flex;flex-direction:column;gap:.15rem}
.schp-lbl{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;color:#4a6080}
.schp-inp{background:#112240;border:1.5px solid rgba(232,93,4,.2);color:#f0f4f8;border-radius:5px;padding:.28rem .55rem;font-size:.8rem;min-width:120px}
.schp-inp:focus{outline:none;border-color:#e85d04}
.schp-inp::placeholder{color:#3a5070}
select.schp-inp{cursor:pointer}
#schp-schichten{padding:.65rem 1rem;background:#060f1e;border-bottom:1px solid rgba(232,93,4,.08);display:flex;gap:.6rem;flex-wrap:wrap}
.sch-chip{border-radius:7px;padding:.3rem .65rem;font-size:.75rem;font-weight:700;flex:1;min-width:160px;display:flex;flex-direction:column;gap:.12rem;border:1.5px solid transparent}
.sch-chip.s1{background:rgba(232,93,4,.12);border-color:rgba(232,93,4,.35)}
.sch-chip.s2{background:rgba(59,130,246,.12);border-color:rgba(59,130,246,.35)}
.sch-chip.s3{background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.35)}
.sch-chip-lbl{font-size:.62rem;text-transform:uppercase;letter-spacing:.4px;font-weight:800}
.s1 .sch-chip-lbl{color:#e85d04}.s2 .sch-chip-lbl{color:#60a5fa}.s3 .sch-chip-lbl{color:#4ade80}
.sch-chip-time{font-size:.78rem;color:#c8d4e0;font-variant-numeric:tabular-nums}
.sch-chip-n{font-size:.68rem;color:#8ba0b4}
#schp-tbl-wrap{overflow-x:auto;padding:.6rem 1rem 0}
#schp-tbl{width:100%;border-collapse:collapse;font-size:.78rem;min-width:560px}
#schp-tbl thead th{background:#0d1b2a;color:#8ba0b4;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.4px;padding:.32rem .45rem;border-bottom:1.5px solid rgba(232,93,4,.18);text-align:left;white-space:nowrap}
#schp-tbl tbody tr:hover td{background:rgba(255,255,255,.02)}
#schp-tbl td{padding:.28rem .4rem;border-bottom:1px solid rgba(255,255,255,.04);vertical-align:middle}
.schp-fi{background:#112240;border:1px solid rgba(232,93,4,.12);color:#f0f4f8;border-radius:4px;padding:.2rem .4rem;font-size:.76rem;width:100%}
.schp-fi:focus{outline:none;border-color:#e85d04}
.schp-fi::placeholder{color:#3a5070}
.schp-sel{background:#112240;border:1px solid rgba(232,93,4,.12);color:#f0f4f8;border-radius:4px;padding:.2rem .3rem;font-size:.74rem;width:100%;cursor:pointer}
.schp-sel:focus{outline:none;border-color:#e85d04}
.sch-badge{display:inline-block;padding:.12rem .45rem;border-radius:4px;font-size:.68rem;font-weight:800;white-space:nowrap}
.sch-badge.s1{background:rgba(232,93,4,.18);color:#e85d04}
.sch-badge.s2{background:rgba(59,130,246,.18);color:#60a5fa}
.sch-badge.s3{background:rgba(34,197,94,.18);color:#4ade80}
.sch-badge.none{background:rgba(255,255,255,.05);color:#3a5070}
.schp-del{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;border-radius:4px;width:26px;height:26px;cursor:pointer;font-size:.85rem;display:flex;align-items:center;justify-content:center;transition:background .12s}
.schp-del:hover{background:rgba(239,68,68,.2)}
#schp-act{padding:.5rem 1rem;border-top:1px solid rgba(232,93,4,.06);display:flex;gap:.45rem;flex-wrap:wrap;align-items:center}
.schp-btn{background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.28rem .8rem;border-radius:5px;cursor:pointer;font-size:.77rem;font-weight:700;transition:background .12s}
.schp-btn:hover{background:rgba(232,93,4,.22)}
.schp-btn.pr{background:#e85d04;border:none;color:#fff}
.schp-btn.pr:hover{background:#f97316}
#schp-ts{font-size:.65rem;color:#2a4060;margin-left:auto}
@media print{
  body>*{display:none!important}
  #schp{display:block!important;border:none;background:#fff!important;color:#000!important;max-width:100%;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  #schp-act{display:none!important}
  #schp-tbl th,#schp-tbl td{border:1px solid #ccc!important;background:#fff!important;color:#000!important}
  .sch-badge{border:1px solid #999!important;color:#000!important;background:#eee!important}
  .schp-fi,.schp-sel{border:1px solid #ccc!important;background:#f5f5f5!important;color:#000!important}
  #schp-schichten{background:#f5f5f5!important}
}
</style>
<div id="schp-hdr">
  <h2>&#128197; Schicht<span>planung</span></h2>
  <span class="schp-bdg">3 Schichten</span>
  <span class="schp-bdg">localStorage</span>
</div>
<div id="schp-cfg">
  <div class="schp-fg">
    <span class="schp-lbl">Einsatz / Ereignis</span>
    <input class="schp-inp" id="schp-ev" placeholder="Ereignisname..." style="min-width:180px" oninput="schpSave()">
  </div>
  <div class="schp-fg">
    <span class="schp-lbl">Einsatzbeginn</span>
    <input class="schp-inp" type="datetime-local" id="schp-start" oninput="schpCalcShifts()">
  </div>
  <div class="schp-fg">
    <span class="schp-lbl">Schichtdauer</span>
    <select class="schp-inp" id="schp-dur" onchange="schpCalcShifts()">
      <option value="4">4 Stunden</option>
      <option value="6">6 Stunden</option>
      <option value="8" selected>8 Stunden</option>
      <option value="12">12 Stunden</option>
    </select>
  </div>
  <div class="schp-fg">
    <span class="schp-lbl">Einsatzort</span>
    <input class="schp-inp" id="schp-ort" placeholder="Ort / Abschnitt..." oninput="schpSave()">
  </div>
</div>
<div id="schp-schichten">
  <!-- rendered by JS -->
</div>
<div id="schp-tbl-wrap">
  <table id="schp-tbl">
    <thead>
      <tr>
        <th style="width:35%">Name</th>
        <th style="width:18%">Organisation</th>
        <th style="width:22%">Funktion / Aufgabe</th>
        <th style="width:15%">Schichtzuweisung</th>
        <th style="width:36px"></th>
      </tr>
    </thead>
    <tbody id="schp-tbody"></tbody>
  </table>
</div>
<div id="schp-act">
  <button class="schp-btn" onclick="schpAdd()">+ Person hinzuf&uuml;gen</button>
  <button class="schp-btn pr" onclick="window.print()">&#128438; Drucken</button>
  <button class="schp-btn" onclick="schpReset()">&#8635; Leeren</button>
  <span id="schp-ts"></span>
</div>
<script>
(function(){
  var rows=[],rid=0;
  var SCH_C=['s1','s2','s3','none'];

  function schpGetCfg(){
    return{
      ev:(document.getElementById('schp-ev')||{}).value||'',
      ort:(document.getElementById('schp-ort')||{}).value||'',
      start:(document.getElementById('schp-start')||{}).value||'',
      dur:parseInt((document.getElementById('schp-dur')||{}).value||'8',10)
    };
  }

  window.schpCalcShifts=function(){
    var cfg=schpGetCfg();
    var wrap=document.getElementById('schp-schichten');
    wrap.innerHTML='';
    var labels=['Schicht 1','Schicht 2','Schicht 3'];
    var cls=['s1','s2','s3'];
    for(var i=0;i<3;i++){
      var chip=document.createElement('div');
      chip.className='sch-chip '+cls[i];
      var timeStr='—';
      if(cfg.start){
        var d=new Date(cfg.start);
        d.setHours(d.getHours()+i*cfg.dur);
        var e=new Date(d);e.setHours(e.getHours()+cfg.dur);
        var fmt=function(dt){return dt.toLocaleString('de-DE',{weekday:'short',day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'});};
        timeStr=fmt(d)+' – '+fmt(e);
      }
      var cnt=rows.filter(function(r){return r.sch===(i+1);}).length;
      chip.innerHTML='<span class="sch-chip-lbl">'+labels[i]+' ('+cfg.dur+'h)</span>'
        +'<span class="sch-chip-time">'+timeStr+'</span>'
        +'<span class="sch-chip-n">'+cnt+' Person'+(cnt!==1?'en':'')+'</span>';
      wrap.appendChild(chip);
    }
    schpSave();
  };

  window.schpAdd=function(name,org,fun,sch){
    rows.push({id:++rid,name:name||'',org:org||'FW',fun:fun||'',sch:sch||0});
    schpRender();schpSave();
  };
  window.schpDel=function(id){rows=rows.filter(function(r){return r.id!==id;});schpRender();schpSave();};
  window.schpUpd=function(id,field,value){
    var r=rows.find(function(r){return r.id===id;});
    if(r){r[field]=(field==='sch'?parseInt(value,10):value);schpCalcShifts();schpRender();}
  };

  function esc(s){var d=document.createElement('div');d.textContent=String(s);return d.innerHTML;}

  function schpRender(){
    var tb=document.getElementById('schp-tbody');
    tb.innerHTML='';
    var ORGS=['FW','THW','RD','HiOrg','Pol','BW','Sonst'];
    rows.forEach(function(r){
      var tr=document.createElement('tr');
      var selOrg=ORGS.map(function(o){return '<option value="'+o+'"'+(r.org===o?' selected':'')+'>'+(o==='Sonst'?'Sonstiges':o)+'</option>';}).join('');
      var selSch='<option value="0"'+(r.sch===0?' selected':'')+'>– keine –</option>'
        +'<option value="1"'+(r.sch===1?' selected':'')+'>Schicht 1</option>'
        +'<option value="2"'+(r.sch===2?' selected':'')+'>Schicht 2</option>'
        +'<option value="3"'+(r.sch===3?' selected':'')+'>Schicht 3</option>';
      var schCls=r.sch>0?'s'+r.sch:'none';
      var schLbl=r.sch>0?'Schicht '+r.sch:'–';
      tr.innerHTML=
        '<td><input class="schp-fi" value="'+esc(r.name)+'" oninput="schpUpd('+r.id+',\'name\',this.value)" placeholder="Vor- und Nachname..."></td>'
        +'<td><select class="schp-sel" onchange="schpUpd('+r.id+',\'org\',this.value)"><option value="" disabled></option>'+selOrg+'</select></td>'
        +'<td><input class="schp-fi" value="'+esc(r.fun)+'" oninput="schpUpd('+r.id+',\'fun\',this.value)" placeholder="Funktion / Aufgabe..."></td>'
        +'<td><select class="schp-sel" onchange="schpUpd('+r.id+',\'sch\',this.value)">'+selSch+'</select></td>'
        +'<td><button class="schp-del" onclick="schpDel('+r.id+')" title="Entfernen">&times;</button></td>';
      tb.appendChild(tr);
    });
    document.getElementById('schp-ts').textContent='Aktualisiert '+new Date().toLocaleTimeString('de-DE');
    schpCalcShifts();
  }

  window.schpSave=function(){
    var cfg=schpGetCfg();
    localStorage.setItem('pt_schicht',JSON.stringify({rows:rows,rid:rid,cfg:cfg}));
  };

  function schpLoad(){
    try{
      var d=JSON.parse(localStorage.getItem('pt_schicht')||'{}');
      if(d.rows){rows=d.rows;rid=d.rid||0;}
      if(d.cfg){
        var c=d.cfg;
        if(c.ev)document.getElementById('schp-ev').value=c.ev;
        if(c.ort)document.getElementById('schp-ort').value=c.ort;
        if(c.start)document.getElementById('schp-start').value=c.start;
        if(c.dur)document.getElementById('schp-dur').value=c.dur;
      }
    }catch(e){}
    schpRender();
    if(!rows.length)schpAdd();
  }

  window.schpReset=function(){
    if(!confirm('Alle Eintr\u00e4ge l\u00f6schen?'))return;
    rows=[];rid=0;schpRender();schpSave();schpAdd();
  };

  schpLoad();
})();
</script>
</div>
    <?php
    return ob_get_clean();
} );
