{{-- resources/views/reports.blade.php --}}
@extends('layouts.complete')
@section('title', 'Reportes automáticos')

@push('head')
  {{-- Plotly is bundled via Vite in reports.js --}}
@endpush

@vite([
    'resources/js/pages/reports.js',
    'resources/css/pages/reports.css',
])

@section('content')
<div id="report-page" class="report-page">
  <div class="report-shell" id="report-shell" data-report-content>
    <header class="report-header">
      <div class="report-header__text">
        <h1>Reportes automáticos</h1>
        <p>Consulta KPIs clave por rango de fechas para Finanzas, Dirección y Mantenimiento.</p>
      </div>
      <div class="report-header__actions">
        <button type="button" id="download-report" class="report-button report-button--ghost">
          Descargar PDF
        </button>
      </div>
    </header>

    <section class="report-card report-card--filters">
      <form id="report-filters" class="report-filters">
        @csrf
        <div class="report-filters__row">
          <label class="report-field">
            <span>Desde</span>
            <input type="date" name="from" id="report-from" required />
          </label>
          <label class="report-field">
            <span>Hasta</span>
            <input type="date" name="to" id="report-to" required />
          </label>
          <label class="report-field">
            <span>Rango rápido</span>
            <select name="quickRange" id="report-quick-range">
              <option value="">Selecciona un rango</option>
              <option value="today">Hoy</option>
              <option value="last7">Última semana</option>
              <option value="month">Mes</option>
            </select>
          </label>
          <label class="report-field">
            <span>Sitio</span>
            <select name="site" id="report-site"></select>
          </label>
          <button type="submit" id="report-apply" class="report-button report-button--primary">
            Aplicar filtros
          </button>
        </div>
      </form>
    </section>

    <section class="report-tabs" id="report-tabs" role="tablist" aria-label="Áreas de reporte">
      <button type="button" class="report-tab is-active" data-area-tab="finanzas" role="tab" aria-selected="true">Finanzas</button>
      <button type="button" class="report-tab" data-area-tab="direccion" role="tab" aria-selected="false">Dirección</button>
      <button type="button" class="report-tab" data-area-tab="mantenimiento" role="tab" aria-selected="false">Mantenimiento</button>
    </section>

    <section class="report-grid" id="report-grid">
      {{-- Cards are rendered by resources/js/pages/reports.js --}}
    </section>
  </div>
</div>
@endsection
