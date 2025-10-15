@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<link rel="stylesheet" href="{{ asset('css/clientes.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="page-shell">
  <div class="page-inner">

    <!-- HEADER + TABS -->
    <header class="header-top">
      <div class="header-left">
        <h1 class="main-title">Gestión de Clientes &amp; Onboarding</h1>
        <div class="tabs" role="tablist" aria-label="Pestañas clientes">
          <button class="tab-btn active" data-panel="panel-clientes" role="tab" aria-controls="panel-clientes" aria-selected="true">Clientes</button>
          <button class="tab-btn" data-panel="panel-onboarding" role="tab" aria-controls="panel-onboarding" aria-selected="false">Onboarding</button>
        </div>
      </div>
    </header>

    <!-- CONTENT AREA -->
    <main class="main-area">
      <!-- PANEL: CLIENTES -->
      <section id="panel-clientes" class="panel active" role="tabpanel" aria-labelledby="tab-clientes">

        <!-- KPI -->
        <div class="kpi-wrap">
          <div class="kpi-card">
            <div class="kpi-number">{{ $clientes->count() }}</div>
            <div class="kpi-label">Clientes</div>
            <div class="kpi-sub">1 activos, 1 onboarding, 1 prospectos</div>
          </div>

          <div class="kpi-card">
            <div class="kpi-number">{{ $clientes->sum(function($c){ return $c->locaciones->count(); }) }}</div>
            <div class="kpi-label">Sites</div>
            <div class="kpi-sub">Total sitios</div>
          </div>

          <div class="kpi-card">
            <div class="kpi-number">{{ $clientes->sum(function($c){ return $c->medidores->count(); }) }}</div>
            <div class="kpi-label">Sensores gestionados</div>
            <div class="kpi-sub">Total medidores</div>
          </div>

          <div class="kpi-card">
            <div class="kpi-number">$@php echo number_format($clientes->sum('mrr') ?? 0,0); @endphp</div>
            <div class="kpi-label">MRR total</div>
            <div class="kpi-sub">Ingresos recurrentes</div>
          </div>

          <div class="kpi-action">
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#createClientModal">+ Agregar cliente</button>
          </div>
        </div>

        <!-- SEARCH / FILTER -->
        <div class="controls">
          <input id="searchCliente" class="search" placeholder="Buscar por cliente o RFC..." />
          <button class="filter">Filtrar estatus</button>
        </div>

        <!-- TABLE: CLIENTES -->
        <div class="table-card">
          <table class="clientes-table">
            <thead>
              <tr>
                <th>Cliente</th><th>Ubicación</th><th>Sites</th><th>Áreas</th>
                <th>Medidores</th><th>Reportes</th><th>Estado</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($clientes as $cliente)
                <tr>
                  <td class="c-name">
                    <a href="{{ route('clientes.show', ['cliente' => $cliente->id]) }}" class="c-link">{{ $cliente->nombre }}</a>
                    <div class="c-sub">{{ $cliente->razon_social }} <span class="rfc">RFC {{ $cliente->rfc ?? '—' }}</span></div>
                  </td>
                  <td>{{ $cliente->ciudad ?? '—' }}</td>
                  <td>{{ $cliente->locaciones->count() }}</td>
                  <td>{{ $cliente->areas->count() }}</td>
                  <td>{{ $cliente->medidores->count() }}</td>
                  <td>{{ $cliente->reportes->count() }}</td>
                  <td>
                    <span class="pill {{ \Illuminate\Support\Str::slug(strtolower($cliente->estado ?? 'sin')) }}">
                      {{ $cliente->estado ?? '—' }}
                    </span>
                  </td>
                  <td class="actions">
                    <a href="{{ route('clientes.show', $cliente) }}" class="icon-btn" title="Ver"><i class="fas fa-eye"></i></a>
                    <button class="icon-btn" data-bs-toggle="modal" data-bs-target="#editClientModal{{ $cliente->id }}" title="Editar"><i class="fas fa-edit"></i></button>
                    <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" style="display:inline">
                      @csrf @method('DELETE')
                      <button class="icon-btn del" onclick="return confirm('¿Eliminar cliente?')" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </form>
                    <label class="switch" title="Estado">
                      <input type="checkbox" class="toggle-status" data-id="{{ $cliente->id }}" {{ ($cliente->estado ?? '') == 'Activo' ? 'checked' : '' }}>
                      <span class="slider"></span>
                    </label>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      </section>

      <!-- PANEL: ONBOARDING -->
      <section id="panel-onboarding" class="panel" role="tabpanel" aria-labelledby="tab-onboarding">

        <div class="kpi-wrap">
          <div class="kpi-card small">
            <div class="kpi-number">{{ $clientes->where('estatus','Onboarding')->count() }}</div>
            <div class="kpi-label">Clientes en onboarding</div>
          </div>
          <div class="kpi-card small">
            @php
              $avg = $clientes->where('estatus','Onboarding')->count() ? round($clientes->where('estatus','Onboarding')->avg('progreso') ?? 0) : 0;
            @endphp
            <div class="kpi-number">{{ $avg }}%</div>
            <div class="kpi-label">Promedio avance</div>
          </div>
          <div class="kpi-card small">
            <div class="kpi-number">0</div>
            <div class="kpi-label">Listos para Go-Live</div>
          </div>
          <div class="kpi-card small">
            <div class="kpi-number">2</div>
            <div class="kpi-label">Pendientes de capacitación</div>
          </div>

          <div class="kpi-action">
            <button class="btn-add" data-bs-toggle="modal" data-bs-target="#createClientModal">+ Agregar cliente</button>
          </div>
        </div>

        <div class="table-card onboarding-card">
          <table class="onboard-table">
            <thead>
              <tr>
                <th>Cliente</th><th>Owner</th><th>Progreso</th><th>Próximo paso</th><th>Fecha objetivo</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($clientes->where('estatus','Onboarding') as $cliente)
                <tr>
                  <td>
                    <div class="c-name"><strong>{{ $cliente->nombre }}</strong><div class="c-sub">{{ $cliente->razon_social }}</div></div>
                  </td>
                  <td>{{ $cliente->owner_name ?? '—' }}</td>
                  <td class="progress-td">
                    <div class="progress-line" aria-hidden="true">
                      <div class="progress-bar" style="width: {{ $cliente->progreso ?? 10 }}%"></div>
                    </div>
                    <div class="chips">
                      @php $steps = ['Contrato','Datos fiscales','Alta de sitios','Configuración de sensores','Facturación','Capacitación','Go-Live']; @endphp
                      @php $done = round((($cliente->progreso ?? 0) / 100) * count($steps)); @endphp
                      @foreach($steps as $i => $s)
                        <span class="chip {{ $i < $done ? 'done' : '' }}">{{ $s }}</span>
                      @endforeach
                    </div>
                  </td>
                  <td>{{ $cliente->proximo_paso ?? '—' }}</td>
                  <td>{{ optional($cliente->fecha_objetivo)->format('Y-m-d') ?? '—' }}</td>
                  <td>
                    <a class="btn small edit" href="#" title="Editar"><i class="fas fa-pen"></i></a>
                    <a class="btn continue" href="#" title="Continuar">Continuar</a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      </section>

    </main>
  </div>
</div>

{{-- MODALES EDIT (uno por cliente) --}}
@foreach ($clientes as $cliente)
<div class="modal fade" id="editClientModal{{ $cliente->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header header-modal">
        <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form action="{{ route('clientes.update', $cliente) }}" method="POST">
        @csrf @method('PUT')
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label>Nombre</label><input name="nombre" class="form-control" value="{{ old('nombre', $cliente->nombre) }}"></div>
            <div class="col-md-6"><label>Razón social</label><input name="razon_social" class="form-control" value="{{ old('razon_social', $cliente->razon_social) }}"></div>
            <!-- resto -->
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endforeach

{{-- MODAL CREAR --}}
<div class="modal fade" id="createClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header header-modal">
        <h5 class="modal-title"><i class="fas fa-user-plus"></i> Crear Cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form action="{{ route('clientes.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6"><label>Nombre</label><input name="nombre" class="form-control" value="{{ old('nombre') }}"></div>
            <div class="col-md-6"><label>Razón social</label><input name="razon_social" class="form-control" value="{{ old('razon_social') }}"></div>
            <!-- resto -->
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
          <button class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // TABS (mostrar solo panel activo)
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected','false');
      });
      btn.classList.add('active');
      btn.setAttribute('aria-selected','true');

      const panelId = btn.getAttribute('data-panel');
      document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
      document.getElementById(panelId).classList.add('active');
    });
  });

  // Toggle status (fetch)
  document.querySelectorAll('.toggle-status').forEach(toggle => {
    toggle.addEventListener('change', function() {
      const id = this.dataset.id;
      const estado = this.checked ? 'Activo' : 'Inactivo';
      fetch(`/clientes/update-status/${id}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ estado })
      })
      .then(r => r.json())
      .then(res => {
        if(res.success){
          const pill = this.closest('tr').querySelector('.pill');
          if (pill) {
            pill.textContent = estado;
            pill.className = 'pill ' + estado.toLowerCase();
          }
        }
      }).catch(e => console.error(e));
    });
  });

  // Search
  const search = document.getElementById('searchCliente');
  if (search) {
    search.addEventListener('input', () => {
      const q = search.value.toLowerCase();
      document.querySelectorAll('.clientes-table tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // Si el servidor devuelve que abra un modal específico
  const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
  const editModalId = @json(session('edit_modal'));
  if(editModalId){
    const el = document.getElementById('editClientModal' + editModalId);
    if(el) new bootstrap.Modal(el).show();
  } else if(hasErrors){
    const cm = document.getElementById('createClientModal');
    if(cm) new bootstrap.Modal(cm).show();
  }
});
</script>

@endsection
