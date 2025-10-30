@extends('layouts.app')

@section('title', 'Vinculación rápida — Mest Energy')

@section('content')

<link rel="stylesheet" href="{{ asset('css/sensores.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page">
  <header class="breadcrumbs">
    <div>
      <div class="title">Vinculación rápida — Mest Energy</div>
      <div class="subtitle">Asigna sites a clientes existentes en segundos.</div>
    </div>

    <div class="controls">
      <button id="bulkAssignBtn" class="btn ghost">Asignar seleccionados</button>
      <a href="/clients/create" class="btn">+ Nuevo cliente</a>
    </div>
  </header>

  <div>
    <div class="metrics">
      <div class="metric">
        <div class="num">{{ $sites->count() }}</div>
        <div class="label">Sites totales</div>
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
        <input id="searchInput" class="search" placeholder="Buscar por site id o nombre..." />
        <div class="pill">Base activa</div>
        <div class="pill">Pendientes: —</div>
      </div>

      <div>
        <div class="thead">
          <div class="cell" style="flex:0 0 40px;">OK</div>
          <div class="cell" style="flex:0 0 220px;">SITE</div>
          <div class="cell">SITE NAME</div>
          <div class="cell small">ASIGNAR A CLIENTE</div>
          <div class="cell" style="flex:0 0 140px; text-align:right;">ACCIÓN</div>
        </div>

        @foreach($sites as $site)
        @php
          $assignedClient = $assignedBySite[$site->site_id] ?? null; // puede ser client id
        @endphp

        <div class="row" data-site="{{ $site->site_id }}">
          <div class="cell" style="flex:0 0 40px;">
            <input type="checkbox" class="row-checkbox" />
          </div>

          <div class="cell" style="flex:0 0 220px; font-weight:700;">
            <span class="site-id">#{{ $site->site_id }}</span> — <span class="site-name" data-site="{{ $site->site_id }}">{{ $site->site_name }}</span>
          </div>

          <div class="cell">{{ $site->site_name }}</div>

          <div class="cell small">
            <select class="select assign-select">
              <option value="">— Ninguno —</option>
              @foreach($clients as $c)
                <option value="{{ $c->id }}"
                  {{ (intval($assignedClient) === intval($c->id)) ? 'selected' : '' }}>
                  {{ $c->nombre }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="cell" style="flex:0 0 140px; text-align:right;">
            <div class="action">
              <button class="link-btn" onclick="viewDetails('{{ $site->site_id }}')">Ver</button>
              <button class="btn single-assign"
                      onclick="vincular(this)"
                      data-site="{{ $site->site_id }}"
                      {{ $assignedClient ? '' : '' }}>
                {{ $assignedClient ? 'Reasignar' : 'Vincular' }}
              </button>
              <button class="link-btn" onclick="openEditModal({{ $site->site_id }}, '{{ addslashes($site->site_name) }}')">Editar site</button>
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
      <div class="notes">Aún no hay movimientos. Asigna sites para ver el historial aquí.</div>
    </div>

    <div class="card">
      <div style="font-weight:700; margin-bottom:8px;">Pasos rápidos</div>
      <ol class="notes">
        <li>Confirma que el site exista en la base de datos.</li>
        <li>Selecciona el cliente en la columna "Asignar a cliente".</li>
        <li>Presiona <strong>Vincular</strong> para completar.</li>
      </ol>
    </div>
  </aside>

</div>

<!-- Modal editar site -->
<div id="editSiteModal" class="modal" style="display:none;">
  <div class="modal-content">
    <h3>Editar nombre del site</h3>
    <input id="editSiteNameInput" type="text" />
    <div style="margin-top:10px;">
      <button id="saveSiteNameBtn" class="btn">Guardar</button>
      <button onclick="closeEditModal()" class="btn ghost">Cancelar</button>
    </div>
  </div>
</div>

<script>
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  // Habilitar botones por fila cuando hay selección
  document.querySelectorAll('.row').forEach(function(row){
    const sel = row.querySelector('.assign-select');
    const btn = row.querySelector('.single-assign');
    if(!sel || !btn) return;
    sel.addEventListener('change', function(){
      btn.disabled = false;
      btn.textContent = sel.value ? 'Vincular' : 'Desasignar';
    });
  });

  function vincular(el){
    const row = el.closest('.row');
    const site = el.dataset.site;
    const cliente = row.querySelector('.assign-select').value; // puede ser '' => desasignar
    el.disabled = true;
    const prevText = el.textContent;
    el.textContent = 'Guardando...';

    fetch("{{ route('sensores.vincular') }}", {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': token,
        'Accept':'application/json'
      },
      body: JSON.stringify({
        site_id: parseInt(site),
        client_id: cliente === '' ? null : parseInt(cliente)
      })
    })
    .then(r => r.json())
    .then(j => {
      if(j.success){
        el.textContent = cliente ? 'Vinculado' : 'Desasignado';
        el.disabled = true;
      } else {
        el.textContent = prevText;
        el.disabled = false;
        alert(j.message || 'Error al vincular');
      }
    })
    .catch(e => {
      console.error(e);
      el.textContent = prevText;
      el.disabled = false;
      alert('Error de conexión');
    });
  }

  function viewDetails(site){
    alert('Abrir detalles del site: ' + site);
    // Implementa modal / fetch si quieres más datos del site
  }

  // Bulk assign
  document.getElementById('bulkAssignBtn').addEventListener('click', function(){
    const rows = Array.from(document.querySelectorAll('.row'));
    const assignments = [];

    rows.forEach(r => {
      const checkbox = r.querySelector('.row-checkbox');
      if(!checkbox || !checkbox.checked) return;
      const sel = r.querySelector('.assign-select');
      // allow unassign if selected blank
      assignments.push({
        site_id: parseInt(r.dataset.site),
        client_id: sel && sel.value ? parseInt(sel.value) : null
      });
    });

    if(assignments.length === 0) return alert('Selecciona filas y clientes para asignar.');

    if(!confirm(`Vas a vincular/desasignar ${assignments.length} sites. Continuar?`)) return;

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
        document.querySelectorAll('.row-checkbox:checked').forEach(cb => cb.checked = false);
        // puedes refrescar la página o actualizar DOM según respuesta si lo prefieres
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

  // búsqueda local simple
  document.getElementById('searchInput').addEventListener('input', function(){
    const q = this.value.toLowerCase();
    document.querySelectorAll('.row').forEach(r => {
      const text = r.textContent.toLowerCase();
      r.style.display = text.includes(q) ? '' : 'none';
    });
  });

  // --- Modal editar site ---
  let editingSiteId = null;
  function openEditModal(siteId, siteName){
    editingSiteId = siteId;
    document.getElementById('editSiteNameInput').value = siteName || '';
    document.getElementById('editSiteModal').style.display = 'block';
  }
  function closeEditModal(){
    editingSiteId = null;
    document.getElementById('editSiteModal').style.display = 'none';
  }
  document.getElementById('saveSiteNameBtn').addEventListener('click', function(){
    const name = document.getElementById('editSiteNameInput').value.trim();
    if(!editingSiteId) return closeEditModal();
    if(name.length === 0) return alert('El nombre no puede quedar vacío.');

    this.disabled = true;
    this.textContent = 'Guardando...';

    fetch("{{ route('sites.updateName') }}", {
      method: 'POST',
      headers: {
        'Content-Type':'application/json',
        'X-CSRF-TOKEN': token,
        'Accept':'application/json'
      },
      body: JSON.stringify({
        site_id: editingSiteId,
        site_name: name
      })
    })
    .then(r => r.json())
    .then(j => {
      if(j.success){
        // actualizar DOM
        document.querySelectorAll('.site-name[data-site="'+editingSiteId+'"]').forEach(el => el.textContent = name);
        closeEditModal();
      } else {
        alert(j.message || 'Error al actualizar nombre');
      }
    })
    .catch(e => { console.error(e); alert('Error de conexión'); })
    .finally(() => {
      this.disabled = false;
      this.textContent = 'Guardar';
    });
  });
</script>

@endsection
