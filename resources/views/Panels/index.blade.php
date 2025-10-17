@extends('layouts.app')

@section('title', 'Mi Panel')

@push('head')
  <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@vite(['resources/css/pages/panels.css','resources/js/pages/panels.js'])

@section('content')
<div class="panel-page">
  <header class="panel-header">
    <h1 class="panel-header__title">Dashboard</h1>
    <div class="panel-header__actions">
      <button id="widget-drawer-toggle" class="panel-button panel-button--primary">
        + Agregar widget
      </button>
      <button id="panel-edit" class="panel-button panel-button--ghost">
        Reordenar
      </button>
      <button id="panel-save" class="panel-button panel-button--ghost" disabled>
        Guardar cambios
      </button>
    </div>
  </header>

  <section class="panel-section panel-section--dashboard">
    <div id="panel-dashboard">
      <div id="panel-dashboard-empty" class="empty-state" hidden>
        <strong>Aún no has agregado widgets.</strong>
        <small>Usa el botón “Agregar widget” para comenzar.</small>
      </div>
      <div class="widget-grid"></div>
    </div>
  </section>
</div>

<aside id="widget-drawer" class="drawer" aria-hidden="true">
  <div class="drawer__panel">
    <header class="drawer__header">
      <h3 class="drawer__title">Catálogo de widgets</h3>
      <button id="widget-drawer-close" class="panel-button panel-button--ghost">Cerrar</button>
    </header>

    <div id="widget-catalog">
      <div id="widget-catalog-empty" class="empty-state" hidden>
        <strong>No encontramos widgets disponibles.</strong>
        <small>Verifica tus permisos o recarga la página.</small>
      </div>
      <div class="widget-catalog__toolbar">
        <div class="widget-catalog__filters">
          <label class="widget-catalog__filter">
            <input type="radio" name="catalog-kind" value="all" checked>
            <span>Todos</span>
          </label>
          <label class="widget-catalog__filter">
            <input type="radio" name="catalog-kind" value="chart">
            <span>Gráficas</span>
          </label>
          <label class="widget-catalog__filter">
            <input type="radio" name="catalog-kind" value="kpi">
            <span>KPI</span>
          </label>
        </div>
        <div class="widget-catalog__search">
          <input type="search" id="widget-search" placeholder="Buscar widget…" autocomplete="off">
        </div>
      </div>
      <div class="widget-catalog"></div>
    </div>
  </div>
</aside>
@endsection
