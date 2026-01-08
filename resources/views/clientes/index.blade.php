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
                @php
                  // Determinar el estado (status) del cliente
                  $estadoCliente = $cliente->estado ?? 'Inactivo';
                  $isActivo = $estadoCliente === 'Activo' || $estadoCliente === 'activo' || $cliente->estado_cliente == 1;
                  
                  // Determinar el estado geográfico para mostrar en la columna "Ubicación"
                  $estadoGeografico = $cliente->ciudad ?? '—';
                @endphp
                <tr>
                  <td class="c-name">
                    <a href="{{ route('clientes.show', ['cliente' => $cliente->id]) }}" class="c-link">{{ $cliente->nombre }}</a>
                    <div class="c-sub">{{ $cliente->razon_social }} <span class="rfc">RFC {{ $cliente->rfc ?? '—' }}</span></div>
                  </td>
                  <td>{{ $estadoGeografico }}</td>
                  <td>{{ $cliente->locaciones->count() }}</td>
                  <td>{{ $cliente->areas->count() }}</td>
                  <td>{{ $cliente->medidores->count() }}</td>
                  <td>{{ $cliente->reportes->count() }}</td>
                  <td>
                    @php
                      $estadoLabel = $catalogoEstados[$cliente->estado_cliente] ?? ($cliente->estado ?? null);
                    @endphp
                    <span class="pill {{ \Illuminate\Support\Str::slug(strtolower($estadoLabel ?? 'sin')) }}">
                      {{ $estadoLabel ?? '—' }}
                    </span>
                  </td>

                  <td class="actions">
                    <a href="{{ route('clientes.show', $cliente) }}" class="icon-btn" title="Ver"><i class="fas fa-eye"></i></a>
                    <!-- Edit button: abre modal único y carga datos via AJAX -->
                    <button class="icon-btn btn-edit" data-id="{{ $cliente->id }}" title="Editar"><i class="fas fa-edit"></i></button>
                    <form action="{{ route('clientes.destroy', $cliente) }}" method="POST" style="display:inline" class="delete-client-form" data-cliente-nombre="{{ $cliente->nombre }}">
                      @csrf @method('DELETE')
                      <button type="button" class="icon-btn btn-delete" title="Eliminar" data-id="{{ $cliente->id }}">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>

                    <label class="switch" title="Estado">
                      <!-- Modificado: verifica si el cliente está activo para marcar el switch -->
                      <input type="checkbox" class="toggle-status" data-id="{{ $cliente->id }}" {{ $isActivo ? 'checked' : '' }}>
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
            <div class="kpi-number">{{ $onboardingClients->count() }}</div>
            <div class="kpi-label">En onboarding</div>
          </div>
          <div class="kpi-card small">
            @php
              $avg = $onboardingClients->count() ? round($onboardingClients->avg(function($c){
                $sensorsDone = (
                    (isset($c->medidores) && $c->medidores->count() > 0)
                    || (!empty($c->site) && $c->site != '')
                    || (isset($c->locaciones) && $c->locaciones->count() > 0)
                ) ? 1 : 0;
                $capDone = ($c->capacitacion ?? 0) ? 1 : 0;
                $goDone = ($c->estado_cliente == 1) ? 1 : 0;
                $doneSteps = $sensorsDone + $capDone + $goDone;
                return intval(round(($doneSteps / 3) * 100));
              })) : 0;
            @endphp
            <div class="kpi-number">{{ $avg }}%</div>
            <div class="kpi-label">Promedio avance</div>
          </div>
          <div class="kpi-card small">
            <div class="kpi-number">
              {{ $onboardingClients->filter(function($c){ 
                  return (!empty($c->sensors_done) && $c->sensors_done) && (!empty($c->cap_done) && $c->cap_done); 
                })->count() }}
            </div>
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
                  $sensorsDone = (
                      (isset($cliente->medidores) && $cliente->medidores->count() > 0)
                      || (!empty($cliente->site) && $cliente->site != '')
                      || (isset($cliente->locaciones) && $cliente->locaciones->count() > 0)
                  );

                  $capDone = ($cliente->capacitacion ?? 0) ? true : false;
                  $goDone = ($cliente->estado_cliente == 1);
                  $doneSteps = ($sensorsDone ? 1 : 0) + ($capDone ? 1 : 0) + ($goDone ? 1 : 0);
                  $progresoCalc = intval(round(($doneSteps / 3) * 100));
                  if (!$sensorsDone) {
                    $nextStep = 'Vincular sensores';
                  } elseif (!$capDone) {
                    $nextStep = 'Capacitación';
                  } elseif (!$goDone) {
                    $nextStep = 'Go-Live';
                  } else {
                    $nextStep = 'Listo';
                  }
                @endphp

                <article class="onboard-card" data-cliente-id="{{ $cliente->id }}">
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
                        <div class="progress-bar" style="width: {{ $progresoCalc }}%"></div>
                      </div>
                      <div class="progress-percent">{{ $progresoCalc }}%</div>

                      <div class="chips">
                        @if($sensorsDone)
                          <span class="chip done">Configuración sensores</span>
                        @else
                          <a href="{{ route('vincular_sensores') }}" class="chip chip-sensors">Configuración sensores</a>
                        @endif

                        <button type="button"
                                class="chip btn-capacitacion {{ $capDone ? 'done' : '' }}"
                                data-cliente-id="{{ $cliente->id }}">
                          Capacitación
                        </button>

                        <button type="button"
                                class="chip btn-go-live {{ $goDone ? 'done' : '' }}"
                                data-cliente-id="{{ $cliente->id }}">
                          Go-Live
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="onboard-right">
                    <div class="next-step">
                      <div><strong>Próximo paso:</strong> <span class="next-step-text">{{ $nextStep }}</span></div>
                    </div>

                    <div class="onboard-actions">
                      <a href="{{ route('clientes.show', $cliente) }}" class="btn small edit" title="Ver detalle"><i class="fas fa-eye"></i></a>
                      <!-- Edita usando el mismo modal único -->
                      <button class="btn small edit btn-edit" data-id="{{ $cliente->id }}" title="Editar"><i class="fas fa-pen"></i></button>
                    </div>
                  </div>
                </article>
              @endforeach
            </div>
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
        <form id="createClientForm" enctype="multipart/form-data">
          <div class="steps">
            <!-- Step 1: Generales -->
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
                  <label>Código postal</label>
                  <!-- Simple input sin búsqueda automática -->
                  <input id="codigo_postal" name="codigo_postal" type="text" class="form-control" maxlength="5" placeholder="Ej: 44100">
                </div>

                <div class="form-group">
                  <label>Estado</label>
                  <select id="estado_select" name="estado" class="form-control" required>
                      <option value="">Selecciona un estado</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Municipio/Ciudad</label>
                  <select id="municipio_select" name="ciudad" class="form-control" required>
                      <option value="">Primero selecciona un estado</option>
                  </select>
                </div>

                <!-- COLONIA ahora input -->
                <div class="form-group">
                  <label>Colonia</label>
                  <input id="colonia_input" name="colonia" type="text" class="form-control" placeholder="Primero selecciona un municipio" disabled>
                </div>

                <div class="form-group">
                  <label>País</label>
                  <input id="pais_input" name="pais" type="text" class="form-control" value="México" readonly>
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
                  <select name="tarifa_region" class="form-control" required>
                    <option value="">Selecciona una región</option>
                    @foreach($catalogoRegiones as $region)
                      <option value="{{ $region->id }}">{{ $region->region }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="form-group">
                  <label>Factor carga</label>
                  <select name="factor_carga" class="form-control" required>
                    <option value="">Selecciona un grupo tarifario</option>
                    @foreach($grupoTarifarios as $g)
                      <option value="{{ $g->id }}" data-factor="{{ $g->factor_carga }}">{{ $g->nombre }}</option>
                    @endforeach
                  </select>
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

<!-- Modal confirmación eliminar -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content create-client-modal">
      <div class="modal-header">
        <h5 class="modal-title">Confirmar eliminación</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p id="confirmDeleteText">¿Eliminar este cliente?</p>
        <div id="deleteErrorMessage" style="display:none;color:red;font-size:13px;"></div>
      </div>
      <div class="modal-footer">
        <button id="deleteCancel" type="button" class="btn btn-secondary create-btn" data-bs-dismiss="modal">Cancelar</button>
        <button id="deleteConfirm" type="button" class="btn btn-danger create-btn">Eliminar</button>
      </div>
    </div>
  </div>
</div>

<!-- EDIT modal: solo "Generales" -->
<div class="modal fade" id="editClientModal" tabindex="-1" aria-hidden="true" aria-labelledby="editClientLabel">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content create-client-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="editClientLabel">Editar cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div id="edit-client-errors"></div>

        <form id="editClientForm" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="cliente_id" id="edit_cliente_id" value="">

          <div class="form-grid">
            <div class="form-group">
              <label>Nombre legal</label>
              <input id="edit_nombre" name="nombre" type="text" class="form-control" required>
            </div>

            <div class="form-group">
              <label>RFC</label>
              <input id="edit_rfc" name="rfc" type="text" class="form-control">
            </div>

            <div class="form-group">
              <label>Razón social (CFDI)</label>
              <input id="edit_razon_social" name="razon_social" type="text" class="form-control" placeholder="">
            </div>

            <div class="form-group">
              <label>Email</label>
              <input id="edit_email" name="email" type="email" class="form-control">
            </div>

            <div class="form-group">
              <label>Teléfono</label>
              <input id="edit_telefono" name="telefono" type="tel" class="form-control">
            </div>

            <div class="form-group">
              <label>Calle</label>
              <input id="edit_calle" name="calle" type="text" class="form-control">
            </div>

            <div class="form-group">
              <label>Número</label>
              <input id="edit_numero" name="numero" type="text" class="form-control">
            </div>

            <div class="form-group">
              <label>Código postal</label>
              <input id="edit_codigo_postal" name="codigo_postal" type="text" class="form-control" maxlength="5">
            </div>

            <div class="form-group">
              <label>Estado</label>
              <select id="edit_estado_select" name="estado" class="form-control">
                <option value="">Selecciona un estado</option>
              </select>
            </div>

            <div class="form-group">
              <label>Municipio/Ciudad</label>
              <select id="edit_municipio_select" name="ciudad" class="form-control">
                <option value="">Primero selecciona un estado</option>
              </select>
            </div>

            <div class="form-group">
              <label>Colonia</label>
              <input id="edit_colonia_input" name="colonia" type="text" class="form-control" placeholder="Primero selecciona un municipio" disabled>
            </div>

            <div class="form-group">
              <label>País</label>
              <input id="edit_pais" name="pais" type="text" class="form-control" value="México" readonly>
            </div>

            <div class="form-group">
              <label>Cambio de Dólar</label>
              <input id="edit_cambio_dolar" name="cambio_dolar" type="number" step="0.01" class="form-control">
            </div>

            <div class="form-group">
              <label>Latitud</label>
              <input id="edit_latitud" name="latitud" type="text" class="form-control">
            </div>

            <div class="form-group">
              <label>Longitud</label>
              <input id="edit_longitud" name="longitud" type="text" class="form-control">
            </div>

            <div class="form-group">
              <label>Contacto nombre</label>
              <input id="edit_contacto_nombre" name="contacto_nombre" type="text" class="form-control">
            </div>

            <div class="form-group">
              <label>Tarifa región</label>
              <select id="edit_tarifa_region" name="tarifa_region" class="form-control">
                <option value="">Selecciona una región</option>
                @foreach($catalogoRegiones as $region)
                  <option value="{{ $region->id }}">{{ $region->region }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label>Factor carga (grupo tarifario)</label>
              <select id="edit_factor_carga" name="factor_carga" class="form-control">
                <option value="">Selecciona un grupo tarifario</option>
                @foreach($grupoTarifarios as $g)
                  <option value="{{ $g->id }}" data-factor="{{ $g->factor_carga }}">{{ $g->nombre }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label>Site (número)</label>
              <input id="edit_site" name="site" type="number" class="form-control" required>
            </div>

            <!-- Contrato mínimo (solo link) -->
            <div class="form-group full" style="margin-top:10px;">
              <label>Contrato actual</label>
              <div id="edit_contrato_actual" style="font-size:13px;"><em>No hay contrato</em></div>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary create-btn" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary create-btn">Guardar cambios</button>
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
  // ============================================
  // DIRECCIONES (cargar estados/municipios/colonias)
  // ============================================
  function cargarEstados() {
    const estadoSelect = document.getElementById('estado_select');
    if (!estadoSelect || typeof mexicoData === 'undefined') return;
    estadoSelect.innerHTML = '<option value="">Selecciona un estado</option>';
    mexicoData.estados.forEach(estado => {
      const option = document.createElement('option');
      option.value = estado;
      option.textContent = estado;
      estadoSelect.appendChild(option);
    });
  }

  function cargarMunicipios() {
    const estadoSelect = document.getElementById('estado_select');
    const municipioSelect = document.getElementById('municipio_select');
    const coloniaInput = document.getElementById('colonia_input');
    const estado = estadoSelect?.value;

    if (!estado) {
      if (municipioSelect) {
        municipioSelect.innerHTML = '<option value="">Selecciona un estado primero</option>';
        municipioSelect.disabled = true;
      }
      if (coloniaInput) {
        coloniaInput.value = '';
        coloniaInput.disabled = true;
        coloniaInput.placeholder = 'Primero selecciona un municipio';
      }
      return;
    }

    if (municipioSelect) {
      municipioSelect.innerHTML = '<option value="">Cargando municipios...</option>';
      municipioSelect.disabled = false;
    }
    if (coloniaInput) {
      coloniaInput.value = '';
      coloniaInput.disabled = true;
      coloniaInput.placeholder = 'Primero selecciona un municipio';
    }

    setTimeout(() => {
      const municipios = mexicoData.municipios[estado] || ["No se encontraron municipios"];
      if (municipioSelect) {
        municipioSelect.innerHTML = '<option value="">Selecciona un municipio</option>';
        municipios.forEach(municipio => {
          const option = document.createElement('option');
          option.value = municipio;
          option.textContent = municipio;
          municipioSelect.appendChild(option);
        });
        municipioSelect.disabled = false;
      }
    }, 300);
  }

  function cargarColonias() {
    const estadoSelect = document.getElementById('estado_select');
    const municipioSelect = document.getElementById('municipio_select');
    const coloniaInput = document.getElementById('colonia_input');
    const estado = estadoSelect?.value;
    const municipio = municipioSelect?.value;

    if (!coloniaInput) return;
    if (!estado || !municipio) {
      coloniaInput.value = '';
      coloniaInput.disabled = true;
      coloniaInput.placeholder = 'Primero selecciona un municipio';
      return;
    }
    coloniaInput.disabled = false;
    coloniaInput.placeholder = 'Escribe la colonia';
  }

  // Edit modal versions
  function cargarEstadosEdit() {
    const estadoSelect = document.getElementById('edit_estado_select');
    if (!estadoSelect || typeof mexicoData === 'undefined') return;
    estadoSelect.innerHTML = '<option value="">Selecciona un estado</option>';
    mexicoData.estados.forEach(estado => {
      const option = document.createElement('option');
      option.value = estado;
      option.textContent = estado;
      estadoSelect.appendChild(option);
    });
  }

  function cargarMunicipiosEdit() {
    const estado = document.getElementById('edit_estado_select')?.value;
    const municipioSelect = document.getElementById('edit_municipio_select');
    const coloniaInput = document.getElementById('edit_colonia_input');

    if (!estado) {
      if (municipioSelect) {
        municipioSelect.innerHTML = '<option value="">Selecciona un estado primero</option>';
        municipioSelect.disabled = true;
      }
      if (coloniaInput) {
        coloniaInput.value = '';
        coloniaInput.disabled = true;
        coloniaInput.placeholder = 'Primero selecciona un municipio';
      }
      return;
    }

    if (municipioSelect) {
      municipioSelect.innerHTML = '<option value="">Cargando municipios...</option>';
      municipioSelect.disabled = false;
    }
    if (coloniaInput) {
      coloniaInput.value = '';
      coloniaInput.disabled = true;
      coloniaInput.placeholder = 'Primero selecciona un municipio';
    }

    setTimeout(() => {
      const municipios = mexicoData.municipios[estado] || ["No se encontraron municipios"];
      if (municipioSelect) {
        municipioSelect.innerHTML = '<option value="">Selecciona un municipio</option>';
        municipios.forEach(municipio => {
          const option = document.createElement('option');
          option.value = municipio;
          option.textContent = municipio;
          municipioSelect.appendChild(option);
        });
        municipioSelect.disabled = false;
      }
    }, 150);
  }

  function cargarColoniasEdit() {
    const estado = document.getElementById('edit_estado_select')?.value;
    const municipio = document.getElementById('edit_municipio_select')?.value;
    const coloniaInput = document.getElementById('edit_colonia_input');

    if (!coloniaInput) return;
    if (!estado || !municipio) {
      coloniaInput.value = '';
      coloniaInput.disabled = true;
      coloniaInput.placeholder = 'Primero selecciona un municipio';
      return;
    }
    coloniaInput.disabled = false;
    coloniaInput.placeholder = 'Escribe la colonia';
  }

  // ============================================
  // ASIGNAR EVENT LISTENERS A DIRECCIONES
  // ============================================
  function asignarEventListenersDirecciones() {
    const estadoSelect = document.getElementById('estado_select');
    const municipioSelect = document.getElementById('municipio_select');

    if (estadoSelect) estadoSelect.addEventListener('change', cargarMunicipios);
    if (municipioSelect) municipioSelect.addEventListener('change', cargarColonias);

    const editEstadoSelect = document.getElementById('edit_estado_select');
    const editMunicipioSelect = document.getElementById('edit_municipio_select');

    if (editEstadoSelect) editEstadoSelect.addEventListener('change', cargarMunicipiosEdit);
    if (editMunicipioSelect) editMunicipioSelect.addEventListener('change', cargarColoniasEdit);
  }

  // Inicializar estados al cargar la página
  cargarEstados();
  cargarEstadosEdit();
  asignarEventListenersDirecciones();

  // ============================================
  // TABS, SEARCH, TOGGLE STATUS
  // ============================================
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
      btn.classList.add('active'); btn.setAttribute('aria-selected','true');
      const panelId = btn.getAttribute('data-panel');
      document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
      document.getElementById(panelId).classList.add('active');
    });
  });

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

  const search = document.getElementById('searchCliente');
  if (search) {
    search.addEventListener('input', () => {
      const q = search.value.toLowerCase();
      document.querySelectorAll('.clientes-table tbody tr').forEach(tr => {
        tr.style.display = tr.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    });
  }

  // ============================================
  // CREATE MODAL (stepper + submit)
  // ============================================
  (function initCreateStepper(){
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

    function limpiarDireccion() {
      const estadoSelect = document.getElementById('estado_select');
      const municipioSelect = document.getElementById('municipio_select');
      const coloniaInput = document.getElementById('colonia_input');

      if (estadoSelect) estadoSelect.value = '';
      if (municipioSelect) {
        municipioSelect.innerHTML = '<option value="">Selecciona un estado primero</option>';
        municipioSelect.disabled = true;
      }
      if (coloniaInput) {
        coloniaInput.value = '';
        coloniaInput.disabled = true;
        coloniaInput.placeholder = 'Primero selecciona un municipio';
      }
    }

    nextBtn.addEventListener('click', () => showStep(current + 1));
    prevBtn.addEventListener('click', () => showStep(current - 1));
    stepBtns.forEach(b => b.addEventListener('click', () => showStep(parseInt(b.dataset.step))));
    modal.addEventListener('show.bs.modal', () => {
      showStep(1);
      form.reset();
      limpiarDireccion();
    });

    // Submit create
    form.addEventListener('submit', (ev) => {
      ev.preventDefault();
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
        body: formData,
        credentials: "same-origin"
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

    showStep(1);
  })();

  // ----------------- EDIT modal (solo generales) -----------------
  (function initEditSimple(){
    const editModalEl = document.getElementById('editClientModal');
    if (!editModalEl) return;
    const editModal = new bootstrap.Modal(editModalEl);
    const editForm = document.getElementById('editClientForm');

    // Hook edit buttons in table and onboarding (btn-edit)
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        if (!id) return alert('ID de cliente no encontrado');

        const errorsBox = document.getElementById('edit-client-errors');
        if (errorsBox) errorsBox.innerHTML = '';

        try {
          const res = await fetch(`/clientes/${id}/edit`, { headers: { 'Accept': 'application/json' } });
          if (!res.ok) throw new Error('Error al obtener datos del servidor');
          const data = await res.json();

          const getEl = id => document.getElementById(id);
          const mappings = [
            ['edit_cliente_id', data.id || id],
            ['edit_nombre', data.nombre ?? ''],
            ['edit_rfc', data.rfc ?? ''],
            ['edit_razon_social', data.razon_social ?? data.razon_fiscal ?? ''],
            ['edit_email', data.email ?? ''],
            ['edit_telefono', data.telefono ?? ''],
            ['edit_calle', data.calle ?? ''],
            ['edit_numero', data.numero ?? ''],
            ['edit_codigo_postal', data.codigo_postal ?? ''],
            ['edit_pais', data.pais ?? 'México'],
            ['edit_cambio_dolar', data.cambio_dolar ?? ''],
            ['edit_latitud', data.latitud ?? ''],
            ['edit_longitud', data.longitud ?? ''],
            ['edit_contacto_nombre', data.contacto_nombre ?? ''],
            ['edit_site', data.site ?? ''],
          ];

          mappings.forEach(([idName, val]) => {
            const el = getEl(idName);
            if (el) {
              if ('value' in el) el.value = val;
              else el.innerText = val;
            } else {
              console.warn(`Elemento no encontrado en DOM: #${idName}`);
            }
          });

          const tarifaEl = getEl('edit_tarifa_region');
          if (tarifaEl && data.tarifa_region) tarifaEl.value = data.tarifa_region;
          const factorEl = getEl('edit_factor_carga');
          if (factorEl && data.factor_carga) factorEl.value = data.factor_carga;

          // Estado geográfico
          const estadoEl = getEl('edit_estado_select');
          if (estadoEl && data.estado) {
            estadoEl.value = data.estado;
            cargarMunicipiosEdit();
            
            // Esperar a que se carguen los municipios
            setTimeout(() => {
              const municipioEl = getEl('edit_municipio_select');
              if (municipioEl && data.ciudad) {
                municipioEl.value = data.ciudad;
              }
              cargarColoniasEdit();
              setTimeout(() => {
                const coloniaInput = getEl('edit_colonia_input');
                if (coloniaInput && data.colonia) {
                  coloniaInput.value = data.colonia;
                  coloniaInput.disabled = false;
                }
              }, 100);
            }, 300);
          }

          const contratoDiv = getEl('edit_contrato_actual');
          if (contratoDiv) {
            const info = data.infoFiscal ?? data.info_fiscal ?? {};
            if (info && info.csf) {
              contratoDiv.innerHTML = `<a href="/clientes/${data.id}/download-contract" target="_blank" rel="noopener">Contrato actual</a>`;
            } else {
              contratoDiv.innerHTML = '<em>No hay contrato</em>';
            }
          } else {
            console.warn('#edit_contrato_actual no encontrado');
          }

          editModal.show();
        } catch (err) {
          console.error(err);
          alert('Error al cargar cliente: ' + err.message);
        }
      });
    });

    editForm.addEventListener('submit', async (ev) => {
      ev.preventDefault();
      const id = document.getElementById('edit_cliente_id').value;
      if (!id) return alert('ID de cliente faltante');
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const url = `/clientes/${id}`;
      const fd = new FormData(editForm);
      fd.append('_method', 'PUT');

      try {
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: fd
        });

        const contentType = res.headers.get('content-type') || '';
        const data = contentType.includes('application/json') ? await res.json() : null;
        const errorsBox = document.getElementById('edit-client-errors');

        if (res.status === 422 && data?.errors) {
          let html = '<div class="alert alert-danger"><ul>';
          Object.values(data.errors).forEach(messages => messages.forEach(m => html += `<li>${m}</li>`));
          html += '</ul></div>';
          if (errorsBox) errorsBox.innerHTML = html;
          return;
        }

        if (!res.ok) {
          const message = data?.message || 'Error al actualizar cliente';
          if (errorsBox) errorsBox.innerHTML = `<div class="alert alert-danger">${message}</div>`;
          return;
        }

        const bsModal = bootstrap.Modal.getInstance(editModalEl);
        if (bsModal) bsModal.hide();
        // Actualizamos la fila o recargamos: aquí recargamos para simplicidad
        window.location.reload();
      } catch (err) {
        console.error(err);
        const errorsBox = document.getElementById('edit-client-errors');
        if (errorsBox) errorsBox.innerHTML = `<div class="alert alert-danger">Error de comunicación: ${err.message}</div>`;
      }
    });
  })();

  // ============================================
  // ONBOARD CARD HELPERS: recalcular progreso y texto siguiente paso
  // ============================================
  function updateOnboardCardState(card) {
    // chips: consider ones with class 'done' as completed
    const chips = card.querySelectorAll('.chips .chip');
    let done = 0;
    let sensorsDone = false, capDone = false, goDone = false;

    chips.forEach(ch => {
      const txt = ch.textContent.trim().toLowerCase();
      if (ch.classList.contains('done')) {
        done++;
        if (txt.includes('sensor')) sensorsDone = true;
        if (txt.includes('capacit')) capDone = true;
        if (txt.includes('go-live') || txt.includes('go live')) goDone = true;
      } else {
        // If chip is not done but is a non-anchor sensor (rare), skip
      }
    });

    // also detect sensor done if chip-sensors is not a link but a span (already covered)
    const percent = Math.round((done / 3) * 100);
    const bar = card.querySelector('.progress-bar');
    const pctEl = card.querySelector('.progress-percent');
    if (bar) bar.style.width = percent + '%';
    if (pctEl) pctEl.textContent = percent + '%';

    const nextStepEl = card.querySelector('.next-step-text');
    let next = 'Listo';
    if (!sensorsDone) next = 'Vincular sensores';
    else if (!capDone) next = 'Capacitación';
    else if (!goDone) next = 'Go-Live';
    if (nextStepEl) nextStepEl.textContent = next;
  }

  function recalcOnboardProgressCards(){
    document.querySelectorAll('.onboard-card').forEach(card => updateOnboardCardState(card));
  }

  recalcOnboardProgressCards();

  // ============================================
  // CAPACITACION y GO-LIVE: confirm modals con actualización DOM
  // ============================================
  const confirmCapModalEl = document.getElementById('confirmCapacitacionModal');
  const confirmGoModalEl = document.getElementById('confirmGoLiveModal');
  let pendingClienteId = null;

  if (confirmCapModalEl) {
    const capModal = new bootstrap.Modal(confirmCapModalEl);
    document.querySelectorAll('.btn-capacitacion').forEach(btn => {
      btn.addEventListener('click', () => {
        pendingClienteId = btn.dataset.clienteId;
        capModal.show();
      });
    });

    document.getElementById('capacitacionConfirm').addEventListener('click', async () => {
      if (!pendingClienteId) return;
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const btn = document.getElementById('capacitacionConfirm');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

      try {
        const res = await fetch(`/clientes/${pendingClienteId}/capacitacion`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify({})
        });
        const data = await res.json().catch(()=>null);
        if (res.ok && data?.success) {
          // Actualizar DOM: buscar la tarjeta onboarding del cliente y marcar capacitación como done
          const card = document.querySelector(`.onboard-card[data-cliente-id="${pendingClienteId}"]`);
          if (card) {
            const capBtn = card.querySelector('.btn-capacitacion');
            if (capBtn) {
              capBtn.classList.add('done');
              capBtn.disabled = true;
            }
            updateOnboardCardState(card);
          }
          capModal.hide();
        } else {
          alert(data?.message || 'Error al marcar capacitación');
        }
      } catch (err) {
        console.error(err);
        alert('Error al comunicar con el servidor');
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });
  }

  if (confirmGoModalEl) {
    const goModal = new bootstrap.Modal(confirmGoModalEl);
    document.querySelectorAll('.btn-go-live').forEach(btn => {
      btn.addEventListener('click', () => {
        pendingClienteId = btn.dataset.clienteId;
        goModal.show();
      });
    });

    document.getElementById('goLiveConfirm').addEventListener('click', async () => {
      if (!pendingClienteId) return;
      const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      const btn = document.getElementById('goLiveConfirm');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

      try {
        const res = await fetch(`/clientes/${pendingClienteId}/go-live`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: JSON.stringify({})
        });
        const data = await res.json().catch(()=>null);
        if (res.ok && data?.success) {
          const card = document.querySelector(`.onboard-card[data-cliente-id="${pendingClienteId}"]`);
          if (card) {
            const goBtn = card.querySelector('.btn-go-live');
            if (goBtn) {
              goBtn.classList.add('done');
              goBtn.disabled = true;
            }
            // También marcar la pill de estado (si tienes one)
            updateOnboardCardState(card);
          }
          goModal.hide();
        } else {
          alert(data?.message || 'Error al marcar Go-Live');
        }
      } catch (err) {
        console.error(err);
        alert('Error al comunicar con el servidor');
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    });
  }

  // ============================================
  // DELETE: confirm modal (reutiliza pendingForm)
  // ============================================
  (function() {
    const deleteModalEl = document.getElementById('confirmDeleteModal');
    if (!deleteModalEl) return;
    const deleteModal = new bootstrap.Modal(deleteModalEl);
    let pendingForm = null;

    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', (ev) => {
        ev.preventDefault();
        const form = btn.closest('form.delete-client-form');
        if (!form) return console.warn('Formulario de borrado no encontrado');
        pendingForm = form;
        const nombre = form.dataset.clienteNombre || btn.closest('tr')?.querySelector('.c-link')?.innerText || 'este cliente';
        document.getElementById('confirmDeleteText').textContent = `¿Confirmas eliminar "${nombre}"? Esta acción no se puede deshacer.`;
        document.getElementById('deleteErrorMessage').style.display = 'none';
        document.getElementById('deleteErrorMessage').textContent = '';
        deleteModal.show();
      });
    });

    document.getElementById('deleteConfirm').addEventListener('click', async () => {
      if (!pendingForm) { deleteModal.hide(); return; }
      const action = pendingForm.getAttribute('action');
      const token = pendingForm.querySelector('input[name="_token"]').value;

      const confirmBtn = document.getElementById('deleteConfirm');
      const originalText = confirmBtn.textContent;
      confirmBtn.disabled = true;
      confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

      const errorDiv = document.getElementById('deleteErrorMessage');
      errorDiv.style.display = 'none';
      errorDiv.textContent = '';

      try {
        const res = await fetch(action, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
          },
          body: new URLSearchParams(new FormData(pendingForm))
        });

        const data = await res.json().catch(() => null);

        if (res.ok) {
          deleteModal.hide();
          const successMsg = document.createElement('div');
          successMsg.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3';
          successMsg.style.zIndex = '9999';
          successMsg.innerHTML = `
            <strong>✓ Éxito!</strong> Cliente eliminado correctamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          `;
          document.body.appendChild(successMsg);
          setTimeout(() => {
            window.location.reload();
          }, 900);
        } else {
          const msg = data?.message || 'No se pudo eliminar el cliente. Inténtalo de nuevo.';
          errorDiv.style.display = 'block';
          errorDiv.textContent = msg;
          errorDiv.className = 'error-message text-danger mt-2';
          confirmBtn.disabled = false;
          confirmBtn.textContent = originalText;
        }
      } catch (err) {
        errorDiv.style.display = 'block';
        errorDiv.textContent = 'Error de conexión. Verifica tu internet e inténtalo de nuevo.';
        errorDiv.className = 'error-message text-danger mt-2';
        confirmBtn.disabled = false;
        confirmBtn.textContent = originalText;
        console.error('Error eliminando cliente:', err);
      }
    });
  })();

  // Abrir modales si el servidor lo indica (errores/edición)
  const hasErrors = @json($errors->any());
  const editModalId = @json(session('edit_modal'));
  if(editModalId){
    const el = document.getElementById('editClientModal');
    if(el) new bootstrap.Modal(el).show();
  } else if(hasErrors){
    const cm = document.getElementById('createClientModal');
    if(cm) new bootstrap.Modal(cm).show();
  }

});
</script>

<script src="{{ asset('js/mexico-data.js') }}"></script>

<!-- Modales de confirmación (simples) -->
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

@endsection