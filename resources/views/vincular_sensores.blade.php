@extends('layouts.app')

@section('title', 'Vincular sensores — Mest Energy')

@section('content')

<link rel="stylesheet" href="{{ asset('css/sensores.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page" style="padding-top: 3rem;">
  <header class="breadcrumbs">
    <div>
      <div class="title">Vincular sensores</div>
      <div class="subtitle">Asigna sites a clientes existentes en segundos.</div>
    </div>

    <div class="controls">

      <!-- Nuevo site -->
      <button id="openCreateSiteBtn" class="btn " data-bs-toggle="modal" data-bs-target="#createSiteModal">+ Nuevo site</button>
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
      </div>

      <div>
        <div class="thead">
          <!-- OK column removida -->
          <div class="cell small">SITE</div>
          <div class="cell">SITE NAME</div>
          <div class="cell ">ASIGNAR A CLIENTE</div>
          <div class="cell" style="flex:0 0 140px; text-align:right;">ACCIÓN</div>
        </div>

        @foreach($sites as $site)
        @php
          $assignedClient = $assignedBySite[$site->site_id] ?? null; // puede ser client id o null
        @endphp

        <div class="row" data-site="{{ $site->site_id }}" data-assigned="{{ $assignedClient ?? '' }}">
          <div class="cell" style=" font-weight:700;">
            <span class="site-id">#{{ $site->site_id }}</span>
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

              <!-- Botón desvincular explícito -->
              <button class="btn btn-sm ghost unlink-btn"
                      onclick="desvincular(this)"
                      data-site="{{ $site->site_id }}">
                Desvincular
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

  // Inicializar filas: habilitar botones y labels correctos
  document.querySelectorAll('.row').forEach(function(row){
    const sel = row.querySelector('.assign-select');
    const btn = row.querySelector('.single-assign');
    const unlinkBtn = row.querySelector('.unlink-btn');
    if(!sel || !btn) return;

    // Desactivar botones si no hay cambios
    btn.disabled = false;
    if (unlinkBtn) unlinkBtn.disabled = false;

    sel.addEventListener('change', function(){
      const hasVal = sel.value && sel.value !== '';
      btn.textContent = hasVal ? 'Vincular' : 'Desasignar';
      // no tocar el disabled; lo dejamos activo para que el usuario confirme
    });

    // inicial text
    const assigned = row.dataset.assigned;
    if (assigned && assigned !== '') {
      btn.textContent = 'Reasignar';
    } else {
      btn.textContent = 'Vincular';
    }
  });

  // FUNCION: desvincular rápido (setea select a vacío y llama a vincular)
  function desvincular(el){
    const row = el.closest('.row');
    const sel = row.querySelector('.assign-select');
    if(!sel) return;
    sel.value = ''; // seleccionar "— Ninguno —"
    // Llamar a la misma lógica de vincular
    const assignBtn = row.querySelector('.single-assign');
    if(assignBtn) vincular(assignBtn);
  }

  // FUNCION: vincular / desasignar
  async function vincular(el){
    const row = el.closest('.row');
    const site = el.dataset.site;
    const sel = row.querySelector('.assign-select');
    const cliente = sel ? sel.value : null; // '' => desasignar

    el.disabled = true;
    const prevText = el.textContent;
    el.textContent = 'Guardando...';

    try {
      const res = await fetch("{{ route('sensores.vincular') }}", {
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
      });

      const j = await res.json().catch(()=>null);

      if (res.ok && j && j.success) {
        // Usar los contadores que nos devuelve el servidor (más fiables)
        const assignedEl = document.querySelector('.metrics .metric:nth-child(3) .num');
        const pendingEl = document.querySelector('.metrics .metric:nth-child(4) .num');

        if (assignedEl && typeof j.assignedCount !== 'undefined') assignedEl.textContent = parseInt(j.assignedCount);
        if (pendingEl && typeof j.pendingCount !== 'undefined') pendingEl.textContent = parseInt(j.pendingCount);

        // actualizar legenda
        try {
          const legendAssigned = document.querySelectorAll('.legend div')[0];
          const legendPending = document.querySelectorAll('.legend div')[1];
          if (legendAssigned && typeof j.assignedCount !== 'undefined') {
            legendAssigned.innerHTML = '<span class="dot" style="background:var(--accent)"></span>Asignados ('+parseInt(j.assignedCount)+')';
          }
          if (legendPending && typeof j.pendingCount !== 'undefined') {
            legendPending.innerHTML = '<span class="dot" style="background:var(--accent-dark)"></span>Pendientes ('+parseInt(j.pendingCount)+')';
          }
        } catch(e){}

        // actualizar donut (calcular %)
        try {
          const total = (typeof j.assignedCount !== 'undefined' && typeof j.pendingCount !== 'undefined') ? (parseInt(j.assignedCount) + parseInt(j.pendingCount)) : null;
          if (total && total > 0) {
            const pct = Math.round((parseInt(j.assignedCount) / total) * 100);
            const dashA = pct + ' ' + (100 - pct);
            const donutStroke = document.querySelector('.donut-svg circle[stroke][transform]');
            if (donutStroke) {
              donutStroke.setAttribute('stroke-dasharray', dashA);
            }
          }
        } catch(e){}

        // actualizar texto del botón y dataset
        if (cliente === '' || cliente === null) {
          el.textContent = 'Desasignado';
          row.dataset.assigned = '';
        } else {
          el.textContent = 'Vinculado';
          row.dataset.assigned = String(cliente);
        }
        el.disabled = true; // no permitir doble click (si quieres permitir nuevos cambios quita esto)
      } else {
        el.textContent = prevText;
        el.disabled = false;
        alert(j?.message || 'Error al vincular');
      }
    } catch (err) {
      console.error(err);
      el.textContent = prevText;
      el.disabled = false;
      alert('Error de conexión');
    }
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
  document.getElementById('saveSiteNameBtn').addEventListener('click', async function(){
    const name = document.getElementById('editSiteNameInput').value.trim();
    if(!editingSiteId) return closeEditModal();
    if(name.length === 0) return alert('El nombre no puede quedar vacío.');

    this.disabled = true;
    this.textContent = 'Guardando...';

    try {
      const res = await fetch("{{ route('sites.updateName') }}", {
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
      });

      const j = await res.json().catch(()=>null);

      if(j && j.success){
        document.querySelectorAll('.site-name[data-site="'+editingSiteId+'"]').forEach(el => el.textContent = name);
        closeEditModal();
      } else {
        alert(j?.message || 'Error al actualizar nombre');
      }
    } catch(e){ console.error(e); alert('Error de conexión'); }
    finally {
      this.disabled = false;
      this.textContent = 'Guardar';
    }
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
