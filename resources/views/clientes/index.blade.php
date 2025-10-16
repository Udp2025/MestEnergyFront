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
            <section class="step-panel active" data-step="1">
              <div class="form-grid">
                <div class="form-group">
                  <label>Nombre legal</label>
                  <input name="nombre" type="text" class="form-control" placeholder="Razón social" required>
                </div>
                <div class="form-group">
                  <label>RFC</label>
                  <input name="rfc" type="text" class="form-control" placeholder="XAXX010101000" >
                </div>

                <div class="form-group">
                  <label>Ciudad</label>
                  <input name="ciudad" type="text" class="form-control" placeholder="Ciudad, Estado">
                </div>
                <div class="form-group">
                  <label>Contacto</label>
                  <input name="contacto" type="text" class="form-control" placeholder="Nombre del responsable">
                </div>

                <div class="form-group">
                  <label>Email</label>
                  <input name="email" type="email" class="form-control" placeholder="contacto@empresa.com">
                </div>
                <div class="form-group">
                  <label>Teléfono</label>
                  <input name="telefono" type="tel" class="form-control" placeholder="+52...">
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


document.addEventListener('DOMContentLoaded', () => {
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

    // stepper buttons active state
    stepBtns.forEach(b => b.classList.toggle('active', parseInt(b.dataset.step) === current));

    // footer controls
    currentStepEl.textContent = current;
    prevBtn.disabled = current === 1;
    if(current === total){
      nextBtn.classList.add('d-none');
      createBtn.classList.remove('d-none');
    } else {
      nextBtn.classList.remove('d-none');
      createBtn.classList.add('d-none');
    }

    // update summary when entering last step
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

  // Next / Prev handlers
  nextBtn.addEventListener('click', () => showStep(current + 1));
  prevBtn.addEventListener('click', () => showStep(current - 1));

  // Stepper click
  stepBtns.forEach(b => b.addEventListener('click', () => showStep(parseInt(b.dataset.step))));

  // When modal shown -> reset to step 1
  modal.addEventListener('show.bs.modal', () => {
    showStep(1);
    form.reset();
    // ensure toggle visual state if any
  });

  // handle submit (aquí podrías cambiar por request AJAX o submit normal)
  form.addEventListener('submit', (ev) => {
    ev.preventDefault();
    // ejemplo simple: recopilar y enviar por fetch
    const fd = new FormData(form);
    const payload = {};
    fd.forEach((v, k) => payload[k] = v);

    // Puedes enviar a tu ruta /clientes con fetch POST (aquí un ejemplo)
    fetch("{{ route('clientes.store') }}", {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        "Accept": "application/json",
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      if(res.success){
        // cerrar modal, refrescar o insertar nuevo cliente en la tabla
        const bsModal = bootstrap.Modal.getInstance(modal);
        if(bsModal) bsModal.hide();
        window.location.reload(); // o actualizar con DOM
      } else {
        // mostrar errores (mejor manejar validación en el servidor)
        alert(res.message || 'Error al crear cliente');
      }
    })
    .catch(e => {
      console.error(e);
      alert('Error al enviar datos');
    });
  });

  // inicializa en caso de que se abra por server-side (si $errors, etc)
  showStep(1);
});

</script>

@endsection
