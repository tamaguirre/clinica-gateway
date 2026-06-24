<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reporte — Categorización Automática de Tickets</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
body{background:#f1f5f9;font-family:'Segoe UI',system-ui,sans-serif}
.hdr{background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#fff;padding:2rem;border-radius:12px;margin-bottom:2rem}
.kpi{background:#fff;border-radius:10px;padding:1.25rem 1.5rem;box-shadow:0 1px 3px rgba(0,0,0,.08);border-left:5px solid;height:100%}
.kpi-lbl{font-size:.7rem;text-transform:uppercase;letter-spacing:.07em;color:#64748b;margin-bottom:.2rem}
.kpi-val{font-size:2rem;font-weight:700;line-height:1.1}
.kpi-sub{font-size:.78rem;color:#94a3b8;margin-top:.2rem}
.cht{background:#fff;border-radius:10px;padding:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.08);height:100%}
.stitle{font-size:1rem;font-weight:600;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.4rem;margin-bottom:1rem}
.bkw{background:#ede9fe;color:#5b21b6;font-weight:500}
.bai{background:#fff7ed;color:#c2410c;font-weight:500}
.oky{color:#16a34a;font-weight:700}
.okn{color:#dc2626}
</style>
</head>
<body>
<div class="container-xl py-4">

<div class="hdr">
  <div class="row align-items-center">
    <div class="col">
      <h1 class="h3 fw-bold mb-1">Reporte de Categorización Automática de Tickets</h1>
      <p class="mb-0 opacity-75 small">Clínica Universitaria</p>
    </div>
    <div class="col-auto text-end small">
      <div class="opacity-75 mb-2">Generado el<br><strong>{{ $stats['generated_at'] }}</strong></div>
      <div>
        <span class="badge" style="background:#f3e8ff;color:#7c3aed;border:1px solid #d8b4fe">🤖 {{ $stats['model'] }}</span>
        <span class="badge" style="background:#fefce8;color:#a16207;border:1px solid #fde047">&#x1F4C4; JSON</span>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#3b82f6">
    <div class="kpi-lbl">Tickets procesados</div>
    <div class="kpi-val text-primary">{{ $stats['total_tickets'] }}</div>
    <div class="kpi-sub">dataset completo</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#16a34a">
    <div class="kpi-lbl">Precisión global</div>
    <div class="kpi-val" style="color:#16a34a">{{ $stats['accuracy'] }}%</div>
    <div class="kpi-sub">{{ $stats['correct'] }} / {{ $evaluated }} evaluados</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#8b5cf6">
    <div class="kpi-lbl">Tiempo total</div>
    <div class="kpi-val" style="color:#8b5cf6">{{ $totalSec }} s</div>
    <div class="kpi-sub">{{ $stats['total_time_ms'] }} ms en total</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#f59e0b">
    <div class="kpi-lbl">Tiempo promedio</div>
    <div class="kpi-val" style="color:#f59e0b">{{ $stats['avg_time_ms'] }} ms</div>
    <div class="kpi-sub">por ticket procesado</div>
  </div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#6366f1">
    <div class="kpi-lbl">Por keyword</div>
    <div class="kpi-val" style="color:#6366f1">{{ $stats['keyword_count'] }}</div>
    <div class="kpi-sub">{{ $kwPct }}% · ~{{ $stats['avg_keyword_time_ms'] }} ms/ticket</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#f97316">
    <div class="kpi-lbl">Por IA</div>
    <div class="kpi-val" style="color:#f97316">{{ $stats['ai_count'] }}</div>
    <div class="kpi-sub">{{ $aiPct }}% · ~{{ $stats['avg_ai_time_ms'] }} ms/ticket</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#0ea5e9">
    <div class="kpi-lbl">Memoria RAM </div>
    <div class="kpi-val" style="color:#0ea5e9">{{ $stats['memory_peak_mb'] }} MB</div>
    <div class="kpi-sub">memory_get_peak_usage()</div>
  </div></div>
  <div class="col-6 col-md-3"><div class="kpi" style="border-color:#64748b">
    <div class="kpi-lbl">CPU </div>
    <div class="kpi-val" style="color:#64748b">{{ $stats['cpu_user_ms'] }} ms</div>
    <div class="kpi-sub">usuario · {{ $stats['cpu_sys_ms'] }} ms sistema</div>
  </div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="cht">
    <div class="stitle">Método de clasificación</div>
    <canvas id="cM"></canvas>
  </div></div>
  <div class="col-md-3"><div class="cht">
    <div class="stitle">Precisión por categoría</div>
    <canvas id="cP"></canvas>
  </div></div>
  <div class="col-md-3"><div class="cht">
    <div class="stitle">Tiempo prom. por método (ms)</div>
    <canvas id="cT"></canvas>
  </div></div>
  <div class="col-md-3"><div class="cht">
    <div class="stitle">Tickets asignados por categoría</div>
    <canvas id="cD"></canvas>
  </div></div>
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <div class="stitle">Resumen por categoría</div>
    <table class="table table-sm table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Categoría</th>
          <th class="text-center">Asignados</th>
          <th class="text-center">Correctos</th>
          <th class="text-center">Incorrectos</th>
          <th class="text-center">Precisión</th>
          <th class="text-center">T. prom. (ms)</th>
        </tr>
      </thead>
      <tbody id="catBody"></tbody>
      <tfoot class="table-light fw-bold">
        <tr>
          <td>Total</td>
          <td class="text-center" id="fT"></td>
          <td class="text-center" id="fC"></td>
          <td class="text-center" id="fI"></td>
          <td class="text-center" id="fA"></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="stitle mb-0">Resultados individuales</div>
      <input id="qS" type="text" class="form-control form-control-sm" style="max-width:280px" placeholder="Buscar...">
    </div>
    <div style="max-height:520px;overflow-y:auto">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-dark sticky-top">
          <tr>
            <th style="width:52px">#</th>
            <th>Vista previa del mensaje</th>
            <th style="width:130px">Esperada</th>
            <th style="width:130px">Asignada</th>
            <th style="width:90px">Método</th>
            <th style="width:75px" class="text-end">ms</th>
            <th style="width:40px" class="text-center">OK</th>
          </tr>
        </thead>
        <tbody id="resB"></tbody>
      </table>
    </div>
    <p class="text-muted small mt-2 mb-0" id="resN"></p>
  </div>
</div>

<footer class="text-center text-muted small mt-4 pb-3">
  Generado por <code>app:ticket-categorization</code> &nbsp;·&nbsp; Ollama &nbsp;·&nbsp; Laravel
</footer>
</div>

<script>
const STATS=@json($stats);
const RESULTS=@json($results);
const CATS=@json($categoriesInResults);
const CLRS={'Orientación':'#3b82f6','Técnicas':'#f59e0b','Urgencia':'#ef4444','Auditorías':'#10b981','Redes':'#06b6d4','Default':'#94a3b8'};

/* Estadísticas por categoría */
function buildCS(data){
  const m={};
  CATS.forEach(c=>{m[c]={total:0,correct:0,incorrect:0,times:[]};});
  data.forEach(r=>{
    const c=r.assigned;
    if(!m[c])m[c]={total:0,correct:0,incorrect:0,times:[]};
    m[c].total++;
    if(r.ok==='✓')m[c].correct++;
    else if(r.ok==='✗')m[c].incorrect++;
    if(r.time_ms!=null)m[c].times.push(r.time_ms);
  });
  return m;
}
const CS=buildCS(RESULTS);

/* Tabla por categoría */
let fT=0,fC=0,fI=0;
CATS.forEach(cat=>{
  const s=CS[cat]||{total:0,correct:0,incorrect:0,times:[]};
  const ev=s.correct+s.incorrect;
  const acc=ev>0?(s.correct/ev*100).toFixed(1)+'%':'—';
  const avgT=s.times.length>0?(s.times.reduce((a,b)=>a+b,0)/s.times.length).toFixed(1):'—';
  const color=CLRS[cat]||CLRS['Default'];
  const bar=ev>0?`<div class="progress mt-1" style="height:5px"><div class="progress-bar" style="width:${(s.correct/ev*100).toFixed(0)}%;background:${color}"></div></div>`:'';
  document.getElementById('catBody').insertAdjacentHTML('beforeend',
    `<tr><td><span class="badge text-white" style="background:${color}">${cat}</span></td>`+
    `<td class="text-center fw-bold">${s.total}</td>`+
    `<td class="text-center text-success fw-bold">${s.correct}</td>`+
    `<td class="text-center text-danger">${s.incorrect}</td>`+
    `<td style="min-width:120px"><span style="color:${color};font-weight:600">${acc}</span>${bar}</td>`+
    `<td class="text-center text-muted small">${avgT}</td></tr>`
  );
  fT+=s.total;fC+=s.correct;fI+=s.incorrect;
});
document.getElementById('fT').textContent=fT;
document.getElementById('fC').textContent=fC;
document.getElementById('fI').textContent=fI;
document.getElementById('fA').textContent=(fC+fI)>0?((fC/(fC+fI))*100).toFixed(1)+'%':'—';

/* Tabla de resultados individuales */
function render(data){
  const okM={'✓':'<span class="oky">✓</span>','✗':'<span class="okn">✗</span>'};
  document.getElementById('resB').innerHTML=data.map(r=>
    `<tr><td class="text-muted">${r.id}</td>`+
    `<td style="white-space:pre-wrap;word-break:break-word;max-width:480px;font-size:.82rem">${(r.preview||'').replace(/</g,'&lt;')}</td>`+
    `<td><small class="text-muted">${r.expected||'—'}</small></td>`+
    `<td style="color:${CLRS[r.assigned]||CLRS['Default']};font-weight:600">${r.assigned||'?'}</td>`+
    `<td><span class="badge ${r.method==='keyword'?'bkw':'bai'}">${r.method}</span></td>`+
    `<td class="text-end text-muted small">${r.time_ms}</td>`+
    `<td class="text-center">${okM[r.ok]||'<span class="text-muted">—</span>'}</td></tr>`
  ).join('');
  document.getElementById('resN').textContent='Mostrando '+data.length+' de '+RESULTS.length+' registros';
}
render(RESULTS);
document.getElementById('qS').addEventListener('input',function(){
  const q=this.value.toLowerCase();
  render(RESULTS.filter(r=>
    String(r.id).includes(q)||(r.preview||'').toLowerCase().includes(q)||
    (r.expected||'').toLowerCase().includes(q)||(r.assigned||'').toLowerCase().includes(q)
  ));
});

/* Chart.js */
Chart.defaults.font.family="'Segoe UI',system-ui,sans-serif";

new Chart(document.getElementById('cM'),{
  type:'doughnut',
  data:{
    labels:['Keyword','IA'],
    datasets:[{data:[STATS.keyword_count,STATS.ai_count],backgroundColor:['#6366f1','#f97316'],borderWidth:2}]
  },
  options:{cutout:'60%',plugins:{legend:{position:'bottom',labels:{boxWidth:12}}}}
});

new Chart(document.getElementById('cP'),{
  type:'bar',
  data:{
    labels:CATS,
    datasets:[{
      label:'Precisión (%)',
      data:CATS.map(c=>{const s=CS[c];const ev=s.correct+s.incorrect;return ev>0?+(s.correct/ev*100).toFixed(1):0;}),
      backgroundColor:CATS.map(c=>CLRS[c]||CLRS['Default']),
      borderRadius:4
    }]
  },
  options:{indexAxis:'y',scales:{x:{max:100,ticks:{callback:v=>v+'%'}}},plugins:{legend:{display:false}}}
});

new Chart(document.getElementById('cT'),{
  type:'bar',
  data:{
    labels:['Keyword','IA'],
    datasets:[{label:'ms',data:[STATS.avg_keyword_time_ms,STATS.avg_ai_time_ms],backgroundColor:['#6366f1','#f97316'],borderRadius:4}]
  },
  options:{scales:{y:{ticks:{callback:v=>v+' ms'}}},plugins:{legend:{display:false}}}
});

new Chart(document.getElementById('cD'),{
  type:'bar',
  data:{
    labels:CATS,
    datasets:[{label:'Tickets',data:CATS.map(c=>CS[c]?.total||0),backgroundColor:CATS.map(c=>CLRS[c]||CLRS['Default']),borderRadius:4}]
  },
  options:{plugins:{legend:{display:false}}}
});
</script>
</body>
</html>
