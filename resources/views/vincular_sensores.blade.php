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

      <!-- Nuevo site -->
      <button id="openCreateSiteBtn" class="btn ghost" data-bs-toggle="modal" data-bs-target="#createSiteModal">+ Nuevo site</button>
    </div>
  </header>

  <div>
    @php
      $totalSites = $sites->count();
      // $assignedBySite: array site_id => client_id (o null)
      $assignedCount = collect($assignedBySite ?? [])->filter(function($v){ return !empty($v) && $v !== null; })->count();
      $pendingCount = $totalSites - $assignedCount;
    @endphp

    <div class="metrics">
      <div class="metric">
        <div class="num">{{ $totalSites }}</div>
        <div class="label">Sites totales</div>
      </div>
      <div class="metric">
        <div class="num">{{ $clients->count() }}</div>
        <div class="label">Clientes</div>
      </div>
      <div class="metric">
        <div class="num">{{ $assignedCount }}</div>
        <div class="label">Asignados</div>
      </div>
      <div class="metric">
        <div class="num">{{ $pendingCount }}</div>
        <div class="label">Pendientes por vincular</div>
      </div>
    </div>

    <div class="main-panel">
      <div class="search-row">
        <input id="searchInput" class="search" placeholder="Buscar por site id o nombre..." />
        <div class="pill">Base activa</div>
        <div class="pill">Pendientes: {{ $pendingCount }}</div>
      </div>

      <div>
        <div class="thead">
          <!-- OK column removida -->
          <div class="cell" style="flex:0 0 220px;">SITE</div>
          <div class="cell">SITE NAME</div>
          <div class="cell small">ASIGNAR A CLIENTE</div>
          <div class="cell" style="flex:0 0 140px; text-align:right;">ACCIÓN</div>
        </div>

        @foreach($sites as $site)
        @php
          $assignedClient = $assignedBySite[$site->site_id] ?? null; // puede ser client id o null
        @endphp

        <div class="row" data-site="{{ $site->site_id }}">
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
              <button class="btn single-assign"
                      onclick="vincular(this)"
                      data-site="{{ $site->site_id }}">
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
          @php
            $pct = $totalSites ? round(($assignedCount / $totalSites) * 100) : 0;
            $dashA = $pct . ' ' . (100 - $pct);
            $offset = 25;
          @endphp
          <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="var(--accent)" stroke-width="8"
                  stroke-dasharray="{{ $dashA }}" stroke-dashoffset="{{ $offset }}" transform="rotate(-90 21 21)"></circle>
          <circle cx="21" cy="21" r="9" fill="var(--panel)" />
        </svg>
      </div>

      <div class="legend">
        <div style="display:flex; gap:8px; align-items:center;"><span class="dot" style="background:var(--accent)"></span>Asignados ({{ $assignedCount }})</div>
        <div style="display:flex; gap:8px; align-items:center;"><span class="dot" style="background:var(--accent-dark)"></span>Pendientes ({{ $pendingCount }})</div>
      </div>
    </div>

    <!-- Últimas vinculaciones y Pasos rápidos eliminados -->
  </aside>

</div>


<!-- Create Site Modal -->
<div class="modal fade" id="createSiteModal" tabindex="-1" aria-hidden="true" aria-labelledby="createSiteLabel">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createSiteLabel">Crear site</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <form id="createSiteForm">
          @csrf
          <div class="form-group">
            <label>ID del site</label>
            <input id="site_id_input" name="site_id" type="number" class="form-control" required />
          </div>
          <div class="form-group">
            <label>Nombre del site</label>
            <input id="site_name_input" name="site_name" type="text" class="form-control" required />
          </div>

          <div style="margin-top:12px; text-align:right;">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" id="createSiteSubmitBtn" class="btn btn-primary">Crear site</button>
          </div>
        </form>

        <div id="createSiteErrors" style="margin-top:10px;"></div>
      </div>
    </div>
  </div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

  

  // Clients list para construir selects en DOM (usado para añadir filas nuevas)
  const clientsList = @json($clients->map(function($c){ return ['id' => $c->id, 'nombre' => $c->nombre]; }));

  // Habilitar botones por fila cuando se cambia el select
  document.querySelectorAll('.row').forEach(function(row){
    const sel = row.querySelector('.assign-select');
    const btn = row.querySelector('.single-assign');
    if(!sel || !btn) return;
    sel.addEventListener('change', function(){
      const hasVal = sel.value && sel.value !== '';
      btn.textContent = hasVal ? 'Vincular' : 'Desasignar';
      btn.disabled = false;
    });
    if (sel.value && sel.value !== '') {
      btn.disabled = false;
    }
  });

  // Vincular / desasignar site (usa route sensores.vincular)
  function vincular(el){
    const row = el.closest('.row');
    const site = el.dataset.site;
    const sel = row.querySelector('.assign-select');
    const cliente = sel ? sel.value : null; // '' => desasignar
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
        client_id: cliente === '' || cliente === null ? null : parseInt(cliente)
      })
    })
    .then(r => r.json())
    .then(j => {
      if(j.success){
        el.textContent = cliente ? 'Vinculado' : 'Desasignado';
        el.disabled = true;
        // actualizar contadores en UI
        try {
          const assignedEl = document.querySelector('.metrics .metric:nth-child(3) .num');
          const pendingEl = document.querySelector('.metrics .metric:nth-child(4) .num');
          let a = parseInt(assignedEl.textContent) || 0;
          let p = parseInt(pendingEl.textContent) || 0;
          if (cliente) { a = a + 1; p = Math.max(0, p - 1); }
          else { a = Math.max(0, a - 1); p = p + 1; }
          assignedEl.textContent = a;
          pendingEl.textContent = p;
          // legend counts
          document.querySelectorAll('.legend div')[0].innerHTML = '<span class="dot" style="background:var(--accent)"></span>Asignados ('+a+')';
          document.querySelectorAll('.legend div')[1].innerHTML = '<span class="dot" style="background:var(--accent-dark)"></span>Pendientes ('+p+')';
        } catch(e){}
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

 

  // ---------------- Create Site (reload on success) ----------------
(function(){
  const form = document.getElementById('createSiteForm');
  const modalEl = document.getElementById('createSiteModal');
  const errorsBox = document.getElementById('createSiteErrors');
  const submitBtn = document.getElementById('createSiteSubmitBtn');
  if (!form) return;

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    errorsBox.innerHTML = '';
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creando...';

    const siteId = document.getElementById('site_id_input').value.trim();
    const siteName = document.getElementById('site_name_input').value.trim();

    if (!siteId || !siteName) {
      errorsBox.innerHTML = '<div class="alert alert-danger">Completa ambos campos.</div>';
      submitBtn.disabled = false;
      submitBtn.textContent = 'Crear site';
      return;
    }

    try {
      const res = await fetch("{{ route('sites.store') }}", {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json'
        },
        body: JSON.stringify({ site_id: parseInt(siteId), site_name: siteName })
      });

      const data = await res.json().catch(()=>null);

      if (res.ok && data?.success !== false) {
        // cerrar modal y recargar la página para que el nuevo site aparezca en la vista renderizada por el servidor
        const bs = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        if (bs) bs.hide();
        // Esperamos un pelín para que el modal termine la animación, luego recargamos
        setTimeout(() => window.location.reload(), 200);
        return;
      }

      const msg = data?.message || 'Error al crear site';
      errorsBox.innerHTML = `<div class="alert alert-danger">${msg}</div>`;
    } catch (err) {
      console.error(err);
      errorsBox.innerHTML = `<div class="alert alert-danger">Error de conexión: ${err.message}</div>`;
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Crear site';
    }
  });
})();

</script>

@endsection
