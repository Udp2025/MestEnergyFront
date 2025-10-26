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
                    @php
                      $estadoLabel = $catalogoEstados[$cliente->estado_cliente] ?? null;
                    @endphp
                    <span class="pill {{ \Illuminate\Support\Str::slug(strtolower($estadoLabel ?? 'sin')) }}">
                      {{ $estadoLabel ?? '—' }}
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

      <!-- PANEL: ONBOARDING (REEMPLAZAR BLOQUE EXISTENTE) -->
<section id="panel-onboarding" class="panel" role="tabpanel" aria-labelledby="tab-onboarding">

  <div class="kpi-wrap">
    <div class="kpi-card small">
      <div class="kpi-number">{{ $onboardingClients->count() }}</div>
      <div class="kpi-label">En onboarding</div>
    </div>
    <div class="kpi-card small">
      @php
        $avg = $onboardingClients->count() ? round($onboardingClients->avg(function($c){ return $c->progreso ?? 0; })) : 0;
      @endphp
      <div class="kpi-number">{{ $avg }}%</div>
      <div class="kpi-label">Promedio avance</div>
    </div>
    <div class="kpi-card small">
      <div class="kpi-number">{{ $onboardingClients->where('progreso', '>=', 100)->count() }}</div>
      <div class="kpi-label">Listos para Go-Live</div>
    </div>
    <div class="kpi-card small">
      <div class="kpi-number">{{ $onboardingClients->where('capacitacion', 1)->count() }}</div>
      <div class="kpi-label">Capacitación agendada</div>
    </div>

    <div class="kpi-action">
      <button class="btn-add" data-bs-toggle="modal" data-bs-target="#createClientModal">+ Agregar cliente</button>
    </div>
  </div>

  <div class="table-card onboarding-card onboarding-grid">
    @if($onboardingClients->isEmpty())
      <div class="empty-onboarding">
        <p>No hay clientes en onboarding.</p>
      </div>
    @else
      <div class="onboarding-list">
        @foreach($onboardingClients as $cliente)
          @php
            $progreso = intval($cliente->progreso ?? 0);
            $steps = ['Configuración sensores','Capacitación','Go-Live'];
            // misma lógica: cuántos pasos completados según el progreso
            $done = intval(round(($progreso / 100) * count($steps)));
          @endphp

          <article class="onboard-card">
            <div class="onboard-left">
              <div class="onboard-title">
                <a href="{{ route('clientes.show', $cliente) }}" class="c-link">{{ $cliente->nombre }}</a>
                <div class="c-sub">{{ $cliente->razon_social ?? '' }}</div>
              </div>
              <div class="onboard-meta">
                <div><strong>Owner:</strong> {{ $cliente->owner_name ?? ($cliente->user->name ?? '—') }}</div>
                <div><strong>Contacto:</strong> {{ $cliente->contacto_nombre ?? '—' }}</div>
              </div>
            </div>

            <div class="onboard-middle">
              <div class="progress-td">
                <div class="progress-line" aria-hidden="true">
                  <div class="progress-bar" style="width: {{ $progreso > 100 ? 100 : $progreso }}%"></div>
                </div>
                <div class="progress-percent">{{ $progreso }}%</div>

                <div class="chips">
  {{-- 1: Configuración sensores -> link a la ruta --}}
  <a href="{{ route('vincular_sensores') }}" class="chip {{ $done >= 1 ? 'done' : '' }}">
    Configuración sensores
  </a>

  {{-- 2: Capacitación -> abre modal --}}
  <button type="button"
          class="chip btn-capacitacion {{ $done >= 2 || $cliente->capacitacion ? 'done' : '' }}"
          data-cliente-id="{{ $cliente->id }}">
    Capacitación
  </button>

  {{-- 3: Go-Live -> abre modal --}}
  <button type="button"
          class="chip btn-go-live {{ $cliente->estado_cliente == 1 ? 'done' : '' }}"
          data-cliente-id="{{ $cliente->id }}">
    Go-Live
  </button>
</div>
              </div>
            </div>

            <div class="onboard-right">
              <div class="next-step">
                <div><strong>Próximo paso:</strong> {{ $cliente->proximo_paso ?? '—' }}</div>
                <div><strong>Fecha objetivo:</strong> {{ optional($cliente->fecha_objetivo)->format('Y-m-d') ?? '—' }}</div>
              </div>

              <div class="onboard-actions">
                <a href="{{ route('clientes.show', $cliente) }}" class="btn small edit" title="Ver detalle"><i class="fas fa-eye"></i></a>
                <button class="btn small edit" data-bs-toggle="modal" data-bs-target="#editClientModal{{ $cliente->id }}" title="Editar"><i class="fas fa-pen"></i></button>
                <a href="#" class="btn continue" onclick="handleContinueOnboarding({{ $cliente->id }})">Continuar</a>
              </div>
            </div>
          </article>
        @endforeach
      </div> {{-- onboarding-list --}}
    @endif
  </div>

</section>

    </main>
  </div>
</div>

<!-- Create Client Modal (3 pasos) -->
<div class="modal fade" id="createClientModal" tabindex="-1" aria-hidden="true" aria-labelledby="createClientLabel">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content create-client-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="createClientLabel">Alta de cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <!-- Stepper -->
        <nav class="stepper" aria-label="Progreso">
          <button class="step-btn active" data-step="1" aria-current="step">Generales</button>
          <button class="step-btn" data-step="2">Fiscales / Contrato</button>
          <button class="step-btn" data-step="3">Plan & Facturación</button>
        </nav>

        <!-- Panels -->
        <form id="createClientForm">
          <div class="steps">
            <!-- Step 1 -->
            <!-- Step 1: Generales (añadir/actualizar campos) -->
<!-- Step 1: Generales (campos completos como inputs) -->
<section class="step-panel active" data-step="1">
  <div class="form-grid">
    <div class="form-group">
      <label>Nombre legal</label>
      <input name="nombre" type="text" class="form-control" required>
    </div>

    <div class="form-group">
      <label>RFC</label>
      <input name="rfc" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Email</label>
      <input name="email" type="email" class="form-control">
    </div>

    <div class="form-group">
      <label>Teléfono</label>
      <input name="telefono" type="tel" class="form-control">
    </div>

    <div class="form-group">
      <label>Calle</label>
      <input name="calle" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Número</label>
      <input name="numero" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Colonia</label>
      <input name="colonia" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Código postal</label>
      <input id="codigo_postal" name="codigo_postal" type="text" class="form-control" maxlength="5" required>
    </div>

    <!-- ahora son inputs normales (no selects) -->
    <div class="form-group">
      <label>Ciudad</label>
      <input id="ciudad_input" name="ciudad" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Estado</label>
      <input id="estado_input" name="estado" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>País</label>
      <input id="pais_input" name="pais" type="text" class="form-control" value="México">
    </div>

    <div class="form-group">
      <label>Cambio de Dólar</label>
      <input name="cambio_dolar" type="number" step="0.01" class="form-control">
    </div>

    <div class="form-group">
      <label>Latitud</label>
      <input name="latitud" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Longitud</label>
      <input name="longitud" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Contacto nombre</label>
      <input name="contacto_nombre" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Tarifa región</label>
      <input name="tarifa_region" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Factor carga</label>
      <input name="factor_carga" type="text" class="form-control">
    </div>

    <div class="form-group">
      <label>Site (número)</label>
      <input name="site" type="number" class="form-control">
    </div>

    

  </div>
</section>



            <!-- Step 2 -->
            <section class="step-panel" data-step="2">
              <div class="form-grid">
                <div class="form-group full">
                  <label>Razón social (CFDI)</label>
                  <input name="razon_fiscal" type="text" class="form-control" placeholder="">
                </div>

                <div class="form-group">
                  <label>Régimen fiscal</label>
                  <select name="regimen" class="form-control">
                    <option value="601">601 - Personas Morales</option>
                    <option value="603">603 - Personas Fisicas</option>
                    <!-- agrega los que necesites -->
                  </select>
                </div>

                <div class="form-group full">
                  <label>Contrato (PDF, máximo 10 MB)</label>
                  <input name="contrato" type="file" class="form-control" accept="application/pdf">
                </div>

                <div class="form-group full">
                  <label>Domicilio fiscal</label>
                  <input name="domicilio" type="text" class="form-control" placeholder="">
                </div>

                <div class="form-group">
                  <label>Uso CFDI</label>
                  <select name="uso_cfdi" class="form-control">
                    <option value="G03">G03 - Gastos en general</option>
                    <option value="P01">P01 - Por definir</option>
                  </select>
                </div>

                <div class="form-group switch-row">
                  <label>Contrato aceptado</label>
                  <label class="switch">
                    <input type="checkbox" name="contrato_aceptado">
                    <span class="slider"></span>
                  </label>
                </div>

                <div class="form-group full">
                  <label>Notas de contrato / condiciones</label>
                  <textarea name="notas_contrato" class="form-control" rows="3" placeholder=""></textarea>
                </div>
              </div>
            </section>

            <!-- Step 3 -->
            <section class="step-panel" data-step="3">
              <div class="form-grid">
                <div class="form-group">
                  <label>Plan</label>
                  <select name="plan" class="form-control">
                    <option>Starter</option>
                    <option>Pro</option>
                    <option>Enterprise</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>MRR (MXN)</label>
                  <input name="mrr" type="number" class="form-control" placeholder="6900">
                </div>

                <div class="form-group">
                  <label>Ciclo</label>
                  <select name="ciclo" class="form-control">
                    <option>Mensual</option>
                    <option>Anual</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Día de corte</label>
                  <input name="dia_corte" type="number" min="1" max="28" class="form-control">
                </div>

                <div class="form-group">
                  <label>Método de pago</label>
                  <select name="metodo_pago" class="form-control">
                    <option>Tarjeta</option>
                    <option>Transferencia</option>
                  </select>
                </div>

                <div class="form-group switch-row">
                  <label>Facturación automática (CFDI)</label>
                  <label class="switch">
                    <input type="checkbox" name="fact_auto">
                    <span class="slider"></span>
                  </label>
                </div>

                <div class="form-group switch-row">
                  <label>Recordatorios de pago</label>
                  <label class="switch">
                    <input type="checkbox" name="recordatorios">
                    <span class="slider"></span>
                  </label>
                </div>

                <div class="form-group full">
                  <label>Resumen</label>
                  <div class="summary-box" id="summaryBox">
                    <div><strong>Cliente:</strong> —</div>
                    <div><strong>Régimen:</strong> —</div>
                    <div><strong>Uso CFDI:</strong> —</div>
                    <div><strong>Plan:</strong> —</div>
                    <div><strong>MRR:</strong> —</div>
                    <div><strong>Ciclo/Día corte:</strong> —</div>
                    <div><strong>Contrato:</strong> —</div>
                  </div>
                </div>
              </div>
            </section>
          </div>

          <!-- Footer (navegación) -->
          <div class="modal-footer step-footer">
            <div class="steps-left">
              <button type="button" class="btn btn-link prev-step" aria-hidden="true" disabled>Atrás</button>
              <div class="step-indicator">Paso <span id="currentStep">1</span> de 3</div>
            </div>

            <div class="steps-right">
              <button type="button" class="btn btn-secondary next-step">Continuar</button>
              <button type="submit" class="btn btn-primary create-btn d-none">+ Crear cliente</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Confirmar Capacitación -->
<div class="modal fade" id="confirmCapacitacionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Capacitación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>¿Confirmas que se recibió la capacitación para este cliente?</p>
      </div>
      <div class="modal-footer">
        <button id="capacitacionCancel" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="capacitacionConfirm" type="button" class="btn btn-primary">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Confirmar Go-Live -->
<div class="modal fade" id="confirmGoLiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar Go-Live</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>¿Deseas poner a este cliente como activo (Go-Live)?</p>
      </div>
      <div class="modal-footer">
        <button id="goLiveCancel" type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button id="goLiveConfirm" type="button" class="btn btn-primary">Sí, poner activo</button>
      </div>
    </div>
  </div>
</div>



<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

  // Variables globales de modales
const confirmCapModalEl = document.getElementById('confirmCapacitacionModal');
const confirmGoModalEl = document.getElementById('confirmGoLiveModal');
let pendingClienteId = null;

if (confirmCapModalEl) {
  const capModal = new bootstrap.Modal(confirmCapModalEl);

  // click en chip capacitacion -> abrir modal y setear id
  document.querySelectorAll('.btn-capacitacion').forEach(btn => {
    btn.addEventListener('click', () => {
      pendingClienteId = btn.dataset.clienteId;
      capModal.show();
    });
  });

  // confirmar
  document.getElementById('capacitacionConfirm').addEventListener('click', () => {
    if (!pendingClienteId) return;
    fetch(`/clientes/${pendingClienteId}/capacitacion`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        // recargar para reflejar cambios (más sencillo)
        window.location.reload();
      } else {
        alert(res.message || 'Error al actualizar capacitación');
      }
    })
    .catch(e => {
      console.error(e);
      alert('Error al comunicar con el servidor');
    });
  });
}

if (confirmGoModalEl) {
  const goModal = new bootstrap.Modal(confirmGoModalEl);

  // click en chip go-live -> abrir modal
  document.querySelectorAll('.btn-go-live').forEach(btn => {
    btn.addEventListener('click', () => {
      pendingClienteId = btn.dataset.clienteId;
      goModal.show();
    });
  });

  // confirmar go-live
  document.getElementById('goLiveConfirm').addEventListener('click', () => {
    if (!pendingClienteId) return;
    fetch(`/clientes/${pendingClienteId}/go-live`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({})
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.location.reload();
      } else {
        alert(res.message || 'Error al marcar Go-Live');
      }
    })
    .catch(e => {
      console.error(e);
      alert('Error al comunicar con el servidor');
    });
  });
}


  // --- TABS (mostrar solo panel activo) ---
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

  // --- Toggle status (fetch) ---
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

  // --- Search local simple ---
  const search = document.getElementById('searchCliente');
  if (search) {
    search.addEventListener('input', () => {
      const q = search.value.toLowerCase();
      document.querySelectorAll('.clientes-table tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // --- Abrir modales si el servidor lo indica ---
  const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
  const editModalId = @json(session('edit_modal'));
  if(editModalId){
    const el = document.getElementById('editClientModal' + editModalId);
    if(el) new bootstrap.Modal(el).show();
  } else if(hasErrors){
    const cm = document.getElementById('createClientModal');
    if(cm) new bootstrap.Modal(cm).show();
  }

  // --- Stepper / Modal create client ---
  const modal = document.getElementById('createClientModal');
  if(!modal) return;

  const steps = Array.from(modal.querySelectorAll('.step-panel'));
  const stepBtns = Array.from(modal.querySelectorAll('.step-btn'));
  const prevBtn = modal.querySelector('.prev-step');
  const nextBtn = modal.querySelector('.next-step');
  const createBtn = modal.querySelector('.create-btn');
  const currentStepEl = modal.querySelector('#currentStep');
  const form = modal.querySelector('#createClientForm');
  const summaryBox = modal.querySelector('#summaryBox');

  let current = 1;
  const total = steps.length;

  function showStep(n){
    current = Math.max(1, Math.min(n, total));
    steps.forEach(s => s.classList.remove('active'));
    const panel = modal.querySelector(`.step-panel[data-step="${current}"]`);
    if(panel) panel.classList.add('active');

    stepBtns.forEach(b => b.classList.toggle('active', parseInt(b.dataset.step) === current));

    currentStepEl.textContent = current;
    prevBtn.disabled = current === 1;
    if(current === total){
      nextBtn.classList.add('d-none');
      createBtn.classList.remove('d-none');
    } else {
      nextBtn.classList.remove('d-none');
      createBtn.classList.add('d-none');
    }

    if(current === total) updateSummary();
  }

  function updateSummary(){
    const data = new FormData(form);
    const text = {
      cliente: data.get('nombre') || '—',
      regimen: (data.get('regimen') ? data.get('regimen') : '—'),
      uso: data.get('uso_cfdi') || '—',
      plan: data.get('plan') || '—',
      mrr: data.get('mrr') ? `$${Number(data.get('mrr')).toLocaleString()}` : '—',
      ciclo: data.get('ciclo') || '—',
      dia: data.get('dia_corte') || '—',
      contrato: data.get('contrato_aceptado') ? 'Aceptado' : 'Pendiente'
    };

    if (summaryBox) {
      summaryBox.innerHTML = `
        <div><strong>Cliente:</strong> ${text.cliente}</div>
        <div><strong>Régimen:</strong> ${text.regimen}</div>
        <div><strong>Uso CFDI:</strong> ${text.uso}</div>
        <div><strong>Plan:</strong> ${text.plan}</div>
        <div><strong>MRR:</strong> ${text.mrr}</div>
        <div><strong>Ciclo/Día corte:</strong> ${text.ciclo} / ${text.dia}</div>
        <div><strong>Contrato:</strong> ${text.contrato}</div>
      `;
    }
  }

  nextBtn.addEventListener('click', () => showStep(current + 1));
  prevBtn.addEventListener('click', () => showStep(current - 1));
  stepBtns.forEach(b => b.addEventListener('click', () => showStep(parseInt(b.dataset.step))));
  modal.addEventListener('show.bs.modal', () => {
    showStep(1);
    form.reset();
  });

  // Submit: envía todo (clientes + info_fiscal + plan) al endpoint /clientes
form.addEventListener('submit', (ev) => {
  ev.preventDefault();

  // limpia mensajes previos
  const errorBoxId = 'create-client-errors';
  let errorBox = modal.querySelector('#' + errorBoxId);
  if (!errorBox) {
    errorBox = document.createElement('div');
    errorBox.id = errorBoxId;
    errorBox.style.marginBottom = '12px';
    modal.querySelector('.modal-body').prepend(errorBox);
  }
  errorBox.innerHTML = '';

  const formData = new FormData(form);
  formData.set('capacitacion', '0');
  formData.set('estado_cliente', '2');

  const contratoAceptado = form.querySelector('input[name="contrato_aceptado"]');
  const factAuto = form.querySelector('input[name="fact_auto"]');
  const recordatorios = form.querySelector('input[name="recordatorios"]');

  formData.set('contrato_aceptado', contratoAceptado && contratoAceptado.checked ? '1' : '0');
  formData.set('fact_auto', factAuto && factAuto.checked ? '1' : '0');
  formData.set('recordatorios', recordatorios && recordatorios.checked ? '1' : '0');

  fetch("{{ route('clientes.store') }}", {
    method: "POST",
    headers: {
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      "Accept": "application/json"
    },
    body: formData
  })
  .then(async (response) => {
    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json') ? await response.json() : null;

    if (response.ok) {
      const bsModal = bootstrap.Modal.getInstance(modal);
      if (bsModal) bsModal.hide();
      window.location.reload();
      return;
    }

    if (response.status === 422 && data?.errors) {
      let html = '<div class="alert alert-danger"><ul>';
      Object.values(data.errors).forEach(messages => {
        messages.forEach(msg => {
          html += `<li>${msg}</li>`;
        });
      });
      html += '</ul></div>';
      errorBox.innerHTML = html;
      return;
    }

    const message = data?.message || 'Error desconocido';
    errorBox.innerHTML = `<div class="alert alert-danger">${message}</div>`;
  })
  .catch(err => {
    console.error(err);
    errorBox.innerHTML = `<div class="alert alert-danger">Error al enviar datos: ${err.message}</div>`;
  });
});


  // Inicializa en step 1
  showStep(1);

}); // end DOMContentLoaded
</script>

@endsection
