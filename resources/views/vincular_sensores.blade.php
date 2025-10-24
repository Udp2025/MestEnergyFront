@extends('layouts.app')

@section('title', 'Vinculación rápida — Mest Energy')

@section('content')

<link rel="stylesheet" href="{{ asset('css/sensores.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page">
  <header class="breadcrumbs">
    <div>
      <div class="title">Vinculación rápida — Mest Energy</div>
      <div class="subtitle">Asigna sensores activos de tu base de datos a clientes existentes en segundos.</div>
    </div>

    <div class="controls">
      <button id="bulkAssignBtn" class="btn ghost">Asignar seleccionados</button>
      <a href="/clients/create" class="btn">+ Nuevo cliente</a>
    </div>
  </header>

  <div>
    <div class="metrics">
      <div class="metric">
        <div class="num">{{ $sensors->count() }}</div>
        <div class="label">Sensores totales</div>
      </div>
      <div class="metric">
        <div class="num">{{ $clients->count() }}</div>

        <div class="label">Clientes</div>
      </div>
      <div class="metric">
        <div class="num">—</div>
        <div class="label">Asignados</div>
      </div>
      <div class="metric">
        <div class="num">—</div>
        <div class="label">Pendientes por vincular</div>
      </div>
    </div>

    <div class="main-panel">
      <div class="search-row">
        <input id="searchInput" class="search" placeholder="Buscar por ID, modelo o fase..." />
        <div class="pill">Base activa</div>
        <div class="pill">Pendientes: —</div>
      </div>

      <div>
        <div class="thead">
          <div class="cell" style="flex:0 0 40px;">OK</div>
          <div class="cell" style="flex:0 0 120px;">SENSOR</div>
          <div class="cell">MODELO</div>
          <div class="cell">SITE</div>
          <div class="cell">RESOLUCIÓN (min)</div>
          <div class="cell small">ASIGNAR A CLIENTE</div>
          <div class="cell" style="flex:0 0 90px; text-align:right;">ACCIÓN</div>
        </div>

        @foreach($sensors as $s)
        @php
          $key = $s->site_id . ':' . $s->device_id;
          $assigned = $assignments[$key] ?? null;
          $clientsForSite = $clientsBySite[$s->site_id] ?? [];
        @endphp

        <div class="row" data-site="{{ $s->site_id }}" data-device="{{ $s->device_id }}">
          <div class="cell" style="flex:0 0 40px;">
            <input type="checkbox" class="row-checkbox" />
          </div>

          <div class="cell" style="flex:0 0 120px; font-weight:700;">{{ $s->device_name ?? ($s->site_id . '-' . $s->device_id) }}</div>
          <div class="cell">{{ $s->device_name }}</div>
          <div class="cell">{{ $s->site_id }}</div>
          <div class="cell">{{ $s->resolution_min }}</div>

          <div class="cell small">
            @php
  $key = (string) ($s->site_id ?? '0');
  $clientsForSite = $clientsBySite->has($key) ? $clientsBySite[$key] : collect();
@endphp

<select class="select assign-select">
  <option value="">Selecciona un cliente...</option>
  @forelse($clientsForSite as $c)
    <option value="{{ $c->id }}" {{ $assigned && $assigned->client_id == $c->id ? 'selected' : '' }}>
      {{ $c->nombre }}
    </option>
  @empty
    <option value="">(No hay clientes para site {{ $s->site_id }})</option>
  @endforelse
</select>

          </div>

          <div class="cell" style="flex:0 0 90px; text-align:right;">
            <div class="action">
              <button class="link-btn" onclick="viewDetails('{{ $s->site_id }}','{{ $s->device_id }}')">Ver</button>
              <button class="btn single-assign" onclick="vincular(this)" data-site="{{ $s->site_id }}" data-device="{{ $s->device_id }}" {{ $assigned ? 'disabled' : '' }}>
                {{ $assigned ? 'Vinculado' : 'Vincular' }}
              </button>
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
        <svg width="180" height="180" viewBox="0 0 42 42" class="donut-svg">
          <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="rgba(0,0,0,0.06)" stroke-width="8"></circle>
          <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="var(--accent)" stroke-width="8"
                  stroke-dasharray="40 60" stroke-dashoffset="25" transform="rotate(-90 21 21)"></circle>
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
        <li>Selecciona el cliente en la columna "Asignar a cliente".li>
        <li>Presiona <strong>Vincular</strong> para completar.</li>
      </ol>
    </div>
  </aside>

</div>

<script>
  // CSRF para fetch
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Habilitar el botón vincular por fila cuando haya selección
  document.querySelectorAll('.row').forEach(function(row){
    const sel = row.querySelector('.assign-select');
    const btn = row.querySelector('.single-assign');
    if(!sel || !btn) return;
    sel.addEventListener('change', function(){
      btn.disabled = sel.value === '';
      if (!btn.disabled) btn.textContent = 'Vincular';
    });
  });

  function vincular(el){
    const row = el.closest('.row');
    const site = el.dataset.site;
    const device = el.dataset.device;
    const cliente = row.querySelector('.assign-select').value;
    if(!cliente) return alert('Selecciona un cliente');
    el.disabled = true;
    el.textContent = 'Guardando...';

    fetch("{{ route('sensores.vincular') }}", {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': token,
        'Accept':'application/json'
      },
      body: JSON.stringify({
        site_id: site,
        device_id: device,
        client_id: cliente
      })
    })
    .then(r => r.json())
    .then(j => {
      if(j.success){
        el.textContent = 'Vinculado';
        el.disabled = true;
      } else {
        el.textContent = 'Vincular';
        el.disabled = false;
        alert(j.message || 'Error al vincular');
      }
    })
    .catch(e => {
      console.error(e);
      el.textContent = 'Vincular';
      el.disabled = false;
      alert('Error de conexión');
    });
  }

  function viewDetails(site, device){
    alert('Abrir detalles: site ' + site + ' device ' + device);
    // aquí podrías abrir un modal con más info usando fetch a un endpoint
  }

  // Bulk assign
  document.getElementById('bulkAssignBtn').addEventListener('click', function(){
    const rows = Array.from(document.querySelectorAll('.row'));
    const assignments = [];

    rows.forEach(r => {
      const checkbox = r.querySelector('.row-checkbox');
      if(!checkbox || !checkbox.checked) return;
      const sel = r.querySelector('.assign-select');
      if(!sel || sel.value === '') return; // ignorar si no hay cliente seleccionado
      assignments.push({
        site_id: r.dataset.site,
        device_id: r.dataset.device,
        client_id: sel.value
      });
    });

    if(assignments.length === 0) return alert('Selecciona filas y clientes para asignar.');

    if(!confirm(`Vas a vincular ${assignments.length} sensores. Continuar?`)) return;

    this.disabled = true;
    this.textContent = 'Asignando...';

    fetch("{{ route('sensores.vincular.bulk') }}", {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': token,
        'Accept':'application/json'
      },
      body: JSON.stringify({ assignments })
    })
    .then(r => r.json())
    .then(j => {
      if(j.success){
        alert(j.message || 'Vinculaciones realizadas');
        // marcar botones como vinculados
        document.querySelectorAll('.row-checkbox:checked').forEach(cb => {
          const r = cb.closest('.row');
          const btn = r.querySelector('.single-assign');
          if(btn){ btn.disabled = true; btn.textContent = 'Vinculado'; }
          cb.checked = false;
        });
      } else {
        alert(j.message || 'Error al vincular en lote');
      }
    })
    .catch(e => { console.error(e); alert('Error de conexión'); })
    .finally(() => {
      this.disabled = false;
      this.textContent = 'Asignar seleccionados';
    });
  });

  // (Opcional) búsqueda local simple
  document.getElementById('searchInput').addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('.row').forEach(r => {
      const text = r.textContent.toLowerCase();
      r.style.display = text.includes(q) ? '' : 'none';
    });
  });
</script>

@endsection
