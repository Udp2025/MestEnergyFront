@extends('layouts.app')

@section('title', 'Vinculación rápida — Mest Energy')

@section('content')
<style>
  :root{
    --bg: #caa18d; 
    --accent: #b84936;
    --accent-dark: #8f422e;
    --panel: rgba(0,0,0,0.06);
    --card: rgba(255,255,255,0.85);
    --text: #2b1f18;
    --muted: rgba(43,31,24,0.65);
    --glass: rgba(255,255,255,0.06);
    --radius: 12px;
  }

  

  .page {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 22px;
  }

  header.breadcrumbs {
    grid-column: 1 / -1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
  }

  .title {
    font-size: 20px;
    font-weight: 700;
  }
  .subtitle { color: var(--muted); font-size: 13px; }

  .controls { display:flex; gap:12px; align-items:center; }
  .btn {
    background: var(--accent);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 10px;
    cursor: pointer;
    box-shadow: 0 6px 14px rgba(0,0,0,0.12);
    font-weight:600;
  }
  .btn.ghost{
    background: transparent;
    color: var(--text);
    border: 1px solid rgba(0,0,0,0.06);
  }

  /* tarjetas de métricas */
  .metrics { display:flex; gap:14px; margin: 10px 0 18px 0; }
  .metric {
    background: var(--card);
    padding: 14px 16px;
    border-radius: var(--radius);
    width: 100%;
    box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    display:flex; flex-direction:column; gap:6px;
  }
  .metric .num { font-size:22px; font-weight:700; }
  .metric .label { font-size:12px; color:var(--muted); }

  .main-panel {
    background: rgba(255,255,255,0.06);
    border-radius: var(--radius);
    padding: 18px;
    min-height: 560px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.02), 0 6px 20px rgba(0,0,0,0.06);
  }

  .search-row { display:flex; gap:12px; margin: 8px 0 18px 0; }
  .search { flex:1; padding:10px 12px; border-radius:10px; border:1px solid rgba(0,0,0,0.06); background: rgba(255,255,255,0.85); }
  .pill { background: var(--card); padding:8px 12px; border-radius: 999px; align-self:center; font-size:13px; }

  /* tabla estilo "tarjetas" */
  .table { width:100%; border-collapse:collapse; }
  .thead { display:flex; gap:8px; color:var(--muted); font-size:12px; padding: 8px 6px; }
  .row { display:flex; gap:8px; align-items:center; padding:12px 6px; border-radius:10px; margin-bottom:10px; background: rgba(255,255,255,0.02); }
  .cell { flex:1; font-size:13px; }
  .cell.small { flex:0 0 160px; }
  .select { padding:8px 10px; border-radius:8px; border:1px solid rgba(0,0,0,0.06); background: white; }
  .action { display:flex; gap:8px; }
  .link-btn { background: transparent; padding:8px 12px; border-radius:8px; border:1px solid rgba(0,0,0,0.06); cursor:pointer; }

  /* sidebar */
  .sidebar { display:flex; flex-direction:column; gap:12px; }
  .card { background: var(--card); padding:14px; border-radius:12px; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
  .donut { display:flex; align-items:center; justify-content:center; height:200px; }
  .legend { display:flex; gap:8px; align-items:center; margin-top:8px; }
  .legend .dot { width:12px; height:12px; border-radius:50%; }

  .notes { font-size:13px; color:var(--muted); }

  footer.small { grid-column:1 / -1; margin-top:18px; color:var(--muted); font-size:12px; }

  /* responsiveness */
  @media (max-width: 980px){
    .page{ grid-template-columns: 1fr; }
    .sidebar{ order: 2; }
  }
</style>

<div class="page">
  <header class="breadcrumbs">
    <div>
      <div class="title">Vinculación rápida — Mest Energy</div>
      <div class="subtitle">Asigna sensores activos de tu base de datos a clientes existentes en segundos.</div>
    </div>

    <div class="controls">
      <button class="btn ghost">Asignar seleccionados</button>
      <button class="btn">+ Nuevo cliente</button>
    </div>
  </header>

  <div>
    <div class="metrics">
      <div class="metric">
        <div class="num">4</div>
        <div class="label">Clientes registrados</div>
      </div>
      <div class="metric">
        <div class="num">7</div>
        <div class="label">Sensores totales</div>
      </div>
      <div class="metric">
        <div class="num">2</div>
        <div class="label">Asignados</div>
      </div>
      <div class="metric">
        <div class="num">5</div>
        <div class="label">Pendientes por vincular</div>
      </div>
    </div>

    <div class="main-panel">
      <div class="search-row">
        <input class="search" placeholder="Buscar por ID, modelo o fase..." />
        <div class="pill">Base activa</div>
        <div class="pill">Pendientes: 5</div>
      </div>

      <div>
        <div class="thead">
          <div class="cell" style="flex:0 0 120px;">SENSOR</div>
          <div class="cell">MODELO</div>
          <div class="cell">FASE</div>
          <div class="cell">ÚLTIMO CONTACTO</div>
          <div class="cell small">ASIGNAR A CLIENTE</div>
          <div class="cell" style="flex:0 0 90px; text-align:right;">ACCIÓN</div>
        </div>

        <!-- filas de ejemplo -->
        @php
          $rows = [
            ['id'=>'SEN-1001','modelo'=>'ME-CT-300','fase'=>'Trifásico','contacto'=>'2025-10-20 14:12'],
            ['id'=>'SEN-1002','modelo'=>'ME-CT-300','fase'=>'Monofásico','contacto'=>'2025-10-21 08:33'],
            ['id'=>'SEN-1004','modelo'=>'ME-CT-600','fase'=>'Trifásico','contacto'=>'2025-10-21 09:11'],
            ['id'=>'SEN-1006','modelo'=>'ME-CT-300','fase'=>'Monofásico','contacto'=>'2025-10-15 17:55'],
            ['id'=>'SEN-1007','modelo'=>'ME-CT-300','fase'=>'Monofásico','contacto'=>'2025-10-21 12:03']
          ];
        @endphp

        @foreach($rows as $r)
        <div class="row" data-id="{{ $r['id'] }}">
          <div class="cell" style="flex:0 0 120px; font-weight:700;">{{ $r['id'] }}</div>
          <div class="cell">{{ $r['modelo'] }}</div>
          <div class="cell">{{ $r['fase'] }}</div>
          <div class="cell">{{ $r['contacto'] }}</div>
          <div class="cell small">
            <select class="select assign-select">
              <option value="">Selecciona un cliente...</option>
              <option value="1">Cliente A</option>
              <option value="2">Cliente B</option>
              <option value="3">Cliente C</option>
            </select>
          </div>
          <div class="cell" style="flex:0 0 90px; text-align:right;">
            <div class="action">
              <button class="link-btn" onclick="viewDetails('{{ $r['id'] }}')">Ver</button>
              <button class="btn" onclick="vincular(this)" disabled>Vincular</button>
            </div>
          </div>
        </div>
        @endforeach

      </div>

    </div>
  </div>

  <aside class="sidebar">
    <div class="card">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div style="font-weight:700">Estado de Vinculación</div>
        <div style="font-size:12px; color:var(--muted)">Última 24 h</div>
      </div>

      <div class="donut">
        <!-- donut SVG simple -->
        <svg width="180" height="180" viewBox="0 0 42 42" class="donut-svg">
          <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="rgba(0,0,0,0.06)" stroke-width="8"></circle>
          <!-- asignados 40% -->
          <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="var(--accent)" stroke-width="8"
                  stroke-dasharray="40 60" stroke-dashoffset="25" transform="rotate(-90 21 21)"></circle>
          <!-- pendientes 60% -->
          <circle cx="21" cy="21" r="9" fill="var(--panel)" />
        </svg>
      </div>

      <div class="legend">
        <div style="display:flex; gap:8px; align-items:center;"><span class="dot" style="background:var(--accent)"></span>Asignados</div>
        <div style="display:flex; gap:8px; align-items:center;"><span class="dot" style="background:var(--accent-dark)"></span>Pendientes</div>
      </div>
    </div>

    <div class="card">
      <div style="font-weight:700; margin-bottom:8px;">Últimas vinculaciones</div>
      <div class="notes">Aún no hay movimientos. Asigna sensores para ver el historial aquí.</div>
    </div>

    <div class="card">
      <div style="font-weight:700; margin-bottom:8px;">Pasos rápidos</div>
      <ol class="notes">
        <li>Confirma que el sensor esté activo (último contacto reciente).</li>
        <li>Selecciona el cliente en la columna "Asignar a cliente".</li>
        <li>Presiona <strong>Vincular</strong> para completar.</li>
      </ol>
    </div>
  </aside>

</div>

<script>
  document.querySelectorAll('.row').forEach(function(row){
    const sel = row.querySelector('.assign-select');
    const btn = row.querySelector('.btn');
    sel.addEventListener('change', function(){
      btn.disabled = sel.value === '';
    });
  });

  function vincular(el){
    const row = el.closest('.row');
    const id = row.dataset.id;
    const cliente = row.querySelector('.assign-select').value;
    if(!cliente) return alert('Selecciona un cliente');
    // aquí llamar a tu endpoint para vincular, por ahora demo
    alert('Vinculando ' + id + ' al cliente ' + cliente);
    el.disabled = true; el.textContent = 'Vinculado';
  }

  function viewDetails(id){
    alert('Abrir detalles de ' + id);
  }
</script>

@endsection

