<?php
/**
 * Plugin Name: Meldevorlagen
 * Description: Vorlagen für BOS-Meldungen (Lagemeldung, SITRAP, Anforderung). Shortcode: [meldevorlagen]
 * Version:     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'meldevorlagen', function (): string {
    ob_start();
    ?>
<div id="melv">
<style>
#melv *{box-sizing:border-box;font-family:system-ui,sans-serif}
#melv{background:#0a1628;color:#f0f4f8;border-radius:10px;overflow:hidden;border:1.5px solid rgba(232,93,4,.25);max-width:900px;margin:0 auto}
#melv-hdr{background:#0f0f1a;border-bottom:2px solid #e85d04;padding:.55rem 1rem;display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
#melv-hdr h2{font-size:.98rem;font-weight:800;margin:0}#melv-hdr h2 span{color:#e85d04}
.melv-bdg{font-size:.63rem;background:rgba(232,93,4,.12);border:1px solid rgba(232,93,4,.3);color:#e85d04;padding:.1rem .4rem;border-radius:3px}
#melv-tabs{display:flex;background:#0d1b2a;border-bottom:1px solid rgba(232,93,4,.1)}
.melv-tab{padding:.45rem .9rem;cursor:pointer;font-size:.78rem;font-weight:700;color:#4a6080;border-bottom:2px solid transparent;transition:all .12s}
.melv-tab:hover{color:#8ba0b4}
.melv-tab.active{color:#e85d04;border-bottom-color:#e85d04;background:rgba(232,93,4,.05)}
#melv-body{display:flex;flex-wrap:wrap;gap:0}
#melv-form{flex:1;min-width:260px;padding:.75rem 1rem;border-right:1px solid rgba(232,93,4,.08)}
#melv-preview{flex:1;min-width:240px;padding:.75rem 1rem;background:#060f1e}
#melv-prev-lbl{font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.5px;color:#4a6080;margin-bottom:.4rem}
#melv-prev-box{background:#0a1628;border:1px solid rgba(232,93,4,.12);border-radius:6px;padding:.6rem .75rem;font-size:.74rem;color:#c8d4e0;white-space:pre-wrap;line-height:1.65;min-height:200px;font-family:'Courier New',monospace;word-break:break-word}
.melv-fg{margin-bottom:.42rem}
.melv-lbl{font-size:.68rem;font-weight:700;color:#8ba0b4;margin-bottom:.18rem;text-transform:uppercase;letter-spacing:.3px}
.melv-inp{width:100%;background:#112240;border:1.5px solid rgba(232,93,4,.15);color:#f0f4f8;border-radius:5px;padding:.3rem .55rem;font-size:.78rem}
.melv-inp:focus{outline:none;border-color:#e85d04}
.melv-inp::placeholder{color:#3a5070}
textarea.melv-inp{resize:vertical;min-height:52px;line-height:1.5}
#melv-ftr{padding:.45rem 1rem;border-top:1px solid rgba(232,93,4,.06);display:flex;gap:.45rem;flex-wrap:wrap;align-items:center}
.melv-btn{background:#e85d04;border:none;color:#fff;padding:.3rem .85rem;border-radius:5px;cursor:pointer;font-size:.78rem;font-weight:700;transition:background .15s}
.melv-btn:hover{background:#f97316}
.melv-btn.s{background:rgba(232,93,4,.12);color:#e85d04;border:1px solid rgba(232,93,4,.3)}
.melv-btn.s:hover{background:rgba(232,93,4,.22)}
#melv-copied{font-size:.72rem;color:#22c55e;display:none}
@media(max-width:600px){#melv-form,#melv-preview{min-width:100%;border-right:none}}
@media print{body>*{display:none!important}
#melv{display:block!important;border:none;background:#fff!important;color:#000!important}
#melv-hdr,#melv-tabs,#melv-form,#melv-ftr,#melv-prev-lbl{display:none!important}
#melv-preview{flex:0 0 100%;background:#fff!important;padding:0}
#melv-prev-box{background:#fff!important;color:#000!important;border:none;font-size:.85rem}}
</style>
<div id="melv-hdr">
  <h2>&#128196; Melde<span>vorlagen</span></h2>
  <span class="melv-bdg">Lagemeldung</span>
  <span class="melv-bdg">SITRAP</span>
  <span class="melv-bdg">Anforderung</span>
</div>
<div id="melv-tabs">
  <div class="melv-tab active" onclick="melvTab('lage',this)">Lagemeldung (FwDV 100)</div>
  <div class="melv-tab" onclick="melvTab('sitrap',this)">SITRAP</div>
  <div class="melv-tab" onclick="melvTab('anford',this)">Anforderung</div>
</div>
<div id="melv-body">
  <div id="melv-form">
    <!-- rendered by JS -->
  </div>
  <div id="melv-preview">
    <div id="melv-prev-lbl">Vorschau / Druckversion</div>
    <div id="melv-prev-box"></div>
  </div>
</div>
<div id="melv-ftr">
  <button class="melv-btn" onclick="window.print()">&#128438; Drucken</button>
  <button class="melv-btn s" onclick="melvCopy()">&#128203; Kopieren</button>
  <button class="melv-btn s" onclick="melvClear()">&#8635; Leeren</button>
  <span id="melv-copied">&#10003; Kopiert!</span>
</div>
<script>
(function(){
  var TEMPLATES={
    lage:{
      title:'Lagemeldung (FwDV 100)',
      fields:[
        {id:'melder',label:'Melder / Einheit',ph:'z.B. EL Müller / FW Muster'},
        {id:'von',label:'Von (Position / Einsatzabschnitt)',ph:'Einsatzort, Abschnitt...'},
        {id:'an',label:'An',ph:'Leitstelle / vorgesetzte Führung...'},
        {id:'ereignis',label:'Ereignis / Schadenslage',ph:'Kurzbeschreibung...'},
        {id:'ort',label:'Einsatzort / Schadenstelle',ph:'Straße, Objekt, GPS...'},
        {id:'personen',label:'Betroffene Personen',ph:'Anzahl, Zustand...'},
        {id:'verletzungen',label:'Verletzungen / Schäden',type:'textarea',ph:'Art und Umfang der Verletzungen / Sachschäden...'},
        {id:'wetter',label:'Wetter',ph:'z.B. bedeckt, 8°C, Wind SW 15 km/h'},
        {id:'eigene',label:'Eigene Kräfte',ph:'z.B. 2/8/22 = 32, 6 Kfz'},
        {id:'massnahmen',label:'Eingeleitete Maßnahmen',type:'textarea',ph:'Maßnahmen, die bereits durchgeführt werden...'},
        {id:'besonderes',label:'Besonderheiten',type:'textarea',ph:'Gefahrstoffe, Einsturz, besondere Risiken...'},
        {id:'anforderung',label:'Anforderungen',type:'textarea',ph:'Benötigte Kräfte, Mittel, Fachberater...'},
        {id:'naechste',label:'Nächste Rückmeldung',ph:'z.B. in 15 min / um 14:30 Uhr'}
      ],
      tpl:function(f,z){return[
        'LAGEMELDUNG',
        '═══════════════════════════════════════',
        'Melder:     '+f('melder'),
        'Von:        '+f('von'),
        'An:         '+f('an'),
        'Zeit:       '+z,
        '──────────────────────────────────────',
        'LAGE:',
        'Ereignis:   '+f('ereignis'),
        'Ort:        '+f('ort'),
        'Personen:   '+f('personen'),
        'Verletzungen / Schäden:',
        '            '+f('verletzungen').replace(/\n/g,'\n            '),
        '──────────────────────────────────────',
        'WETTER:     '+f('wetter'),
        '──────────────────────────────────────',
        'EIGENE LAGE:',
        'Kräfte:     '+f('eigene'),
        'Maßnahmen:',
        '            '+f('massnahmen').replace(/\n/g,'\n            '),
        'Besonderheiten:',
        '            '+f('besonderes').replace(/\n/g,'\n            '),
        '──────────────────────────────────────',
        'ANFORDERUNGEN:',
        '            '+f('anforderung').replace(/\n/g,'\n            '),
        '──────────────────────────────────────',
        'Nächste Rückmeldung: '+f('naechste'),
        '═══════════════════════════════════════'
      ].join('\n');}
    },
    sitrap:{
      title:'SITRAP – Situation Report',
      fields:[
        {id:'einheit',label:'Einheit / Stab',ph:'z.B. EL / Zug 1'},
        {id:'bezug',label:'Bezug (Einsatz / Auftrag)',ph:'Kurztitel des Einsatzes...'},
        {id:'lage',label:'1. Aktuelle Lage / Schadenslage',type:'textarea',ph:'Was passiert, was ist der Stand?'},
        {id:'eigene2',label:'2. Eigene Kräfte / Mittel',type:'textarea',ph:'Eingesetzte Kräfte, Stärke, besondere Ausrüstung...'},
        {id:'durch',label:'3. Durchgeführte Maßnahmen',type:'textarea',ph:'Was wurde bisher unternommen?'},
        {id:'geplant',label:'4. Geplante Maßnahmen',type:'textarea',ph:'Was wird als nächstes getan?'},
        {id:'anforderung2',label:'5. Anforderungen / Meldungen',type:'textarea',ph:'Benötigte Kräfte, Mittel oder besondere Meldungen...'},
        {id:'naechste2',label:'6. Nächste Rückmeldung',ph:'Zeitpunkt der nächsten SITRAP...'}
      ],
      tpl:function(f,z){return[
        'SITUATION REPORT (SITRAP)',
        '═══════════════════════════════════════',
        'Einheit:  '+f('einheit'),
        'Zeit:     '+z,
        'Bezug:    '+f('bezug'),
        '──────────────────────────────────────',
        '1. AKTUELLE LAGE:',
        f('lage').split('\n').map(function(l){return '   '+l;}).join('\n'),
        '──────────────────────────────────────',
        '2. EIGENE KRÄFTE:',
        f('eigene2').split('\n').map(function(l){return '   '+l;}).join('\n'),
        '──────────────────────────────────────',
        '3. DURCHGEFÜHRTE MAẞNAHMEN:',
        f('durch').split('\n').map(function(l){return '   '+l;}).join('\n'),
        '──────────────────────────────────────',
        '4. GEPLANTE MAẞNAHMEN:',
        f('geplant').split('\n').map(function(l){return '   '+l;}).join('\n'),
        '──────────────────────────────────────',
        '5. ANFORDERUNGEN:',
        f('anforderung2').split('\n').map(function(l){return '   '+l;}).join('\n'),
        '──────────────────────────────────────',
        'NÄCHSTE RÜCKMELDUNG: '+f('naechste2'),
        '═══════════════════════════════════════'
      ].join('\n');}
    },
    anford:{
      title:'Anforderung von Kräften / Mitteln',
      fields:[
        {id:'avon',label:'Von (anfordernd)',ph:'Einheit, Name, Dienstgrad...'},
        {id:'aan',label:'An (Leitstelle / Einsatzabschnitt)',ph:'z.B. IRLS Emsland, EL...'},
        {id:'art',label:'Angefordert: Art der Kräfte / Mittel',type:'textarea',ph:'z.B. 1x RTW, 1x DLK 23/12, 1x CBRN-Erkunder...'},
        {id:'anzahl',label:'Anzahl / Stärke',ph:'z.B. 2 Fahrzeuge, 12 Kräfte'},
        {id:'begruendung',label:'Begründung / Einsatzauftrag',type:'textarea',ph:'Warum werden diese Kräfte benötigt?'},
        {id:'aort',label:'Einsatzort / Anfahrt',ph:'genaue Adresse, ggf. GPS-Koordinaten'},
        {id:'asp',label:'Ansprechpartner vor Ort',ph:'Name, Funktion, Dienstgrad'},
        {id:'funk',label:'Funk / Telefon',ph:'Kanal / Rufname / Mobilnummer'},
        {id:'prio',label:'Priorität',ph:'Dringlich / Normal / Voranmeldung'}
      ],
      tpl:function(f,z){return[
        'ANFORDERUNG VON KRÄFTEN / MITTELN',
        '═══════════════════════════════════════',
        'Von:          '+f('avon'),
        'An:           '+f('aan'),
        'Zeit:         '+z,
        '──────────────────────────────────────',
        'ANGEFORDERT:',
        f('art').split('\n').map(function(l){return '  '+l;}).join('\n'),
        '',
        'Anzahl:       '+f('anzahl'),
        'Priorität:    '+f('prio'),
        '──────────────────────────────────────',
        'BEGRÜNDUNG:',
        f('begruendung').split('\n').map(function(l){return '  '+l;}).join('\n'),
        '──────────────────────────────────────',
        'EINSATZORT:   '+f('aort'),
        'Ansprechp.:   '+f('asp'),
        'Funk / Tel.:  '+f('funk'),
        '═══════════════════════════════════════'
      ].join('\n');}
    }
  };

  var curTpl='lage';

  function getVal(id){var el=document.getElementById('melv-f-'+id);return el?el.value.trim():'';}
  function now(){return new Date().toLocaleString('de-DE');}

  function renderForm(){
    var T=TEMPLATES[curTpl];
    var form=document.getElementById('melv-form');
    form.innerHTML='';
    T.fields.forEach(function(f){
      var div=document.createElement('div');div.className='melv-fg';
      var lbl=document.createElement('div');lbl.className='melv-lbl';lbl.textContent=f.label;
      var inp;
      if(f.type==='textarea'){
        inp=document.createElement('textarea');
        inp.className='melv-inp';inp.rows=2;
      } else {
        inp=document.createElement('input');
        inp.className='melv-inp';inp.type='text';
      }
      inp.id='melv-f-'+f.id;inp.placeholder=f.ph||'';
      inp.addEventListener('input',updatePreview);
      div.appendChild(lbl);div.appendChild(inp);
      form.appendChild(div);
    });
    updatePreview();
  }

  function updatePreview(){
    var T=TEMPLATES[curTpl];
    var text=T.tpl(getVal,now());
    document.getElementById('melv-prev-box').textContent=text;
  }

  window.melvTab=function(key,el){
    curTpl=key;
    document.querySelectorAll('#melv-tabs .melv-tab').forEach(function(t){t.classList.remove('active');});
    el.classList.add('active');
    renderForm();
  };

  window.melvCopy=function(){
    var text=document.getElementById('melv-prev-box').textContent;
    if(navigator.clipboard){
      navigator.clipboard.writeText(text).then(function(){
        var msg=document.getElementById('melv-copied');msg.style.display='inline';
        setTimeout(function(){msg.style.display='none';},2000);
      });
    } else {
      var ta=document.createElement('textarea');ta.value=text;document.body.appendChild(ta);
      ta.select();document.execCommand('copy');document.body.removeChild(ta);
      var msg=document.getElementById('melv-copied');msg.style.display='inline';
      setTimeout(function(){msg.style.display='none';},2000);
    }
  };

  window.melvClear=function(){
    if(!confirm('Alle Felder leeren?'))return;
    document.querySelectorAll('#melv-form .melv-inp').forEach(function(el){el.value='';});
    updatePreview();
  };

  renderForm();
})();
</script>
</div>
    <?php
    return ob_get_clean();
} );
