@extends('layouts.app')

@section('title', 'Alertas de KPI')

@push('head')
    @vite(['resources/css/pages/site_alerts.css', 'resources/js/pages/site_alerts.js'])
@endpush

@section('content')
<div class="alerts-page">
    <header class="alerts-header">
        <div>
            <h1>Alertas personalizadas</h1>
            <p>Configura umbrales sobre tus KPIs y recibe notificaciones en tiempo real.</p>
        </div>
        <div class="alerts-header__meta">
            <span class="pill pill--info" id="alertsTotal">0 alertas activas</span>
            <button type="button" class="pill pill--ghost" data-refresh-alerts>
                <i class="fa fa-sync" aria-hidden="true"></i> Actualizar
            </button>
        </div>
    </header>

    <div class="alerts-grid">
        <section class="card alerts-card">
            <h2>Crear / editar alerta</h2>
            <form id="alertForm" class="alert-form">
                <input type="hidden" id="alertId" name="alert_id">
                <div class="form-group">
                    <label for="kpiSlug">Indicador</label>
                    <select id="kpiSlug" name="kpi_slug" required></select>
                    <small id="kpiDescription" class="form-hint"></small>
                </div>
                <div class="form-group">
                    <label for="siteSelector">Sitio</label>
                    <select id="siteSelector" name="site_id" data-site-picker disabled>
                        <option value="">Selecciona un sitio</option>
                    </select>
                    <small class="form-hint" data-site-hint>Solo para administradores globales.</small>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="comparisonOperator">Condición</label>
                        <select id="comparisonOperator" name="comparison_operator" required>
                            <option value="above">Mayor que</option>
                            <option value="below">Menor que</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="thresholdValue">Umbral</label>
                        <div class="input-with-addon">
                            <input type="number" step="any" id="thresholdValue" required>
                            <span class="input-addon" id="thresholdUnit">—</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cooldownMinutes">Repetir cada</label>
                        <div class="input-with-addon">
                            <input type="number" min="1" max="1440" id="cooldownMinutes" value="30">
                            <span class="input-addon">min</span>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="saveAlert">Guardar alerta</button>
                    <button type="button" class="btn btn-ghost" id="resetForm">Limpiar</button>
                </div>
                <div class="form-notice" id="alertFormNotice" role="status"></div>
            </form>
        </section>

        <section class="card alerts-card">
            <div class="card-heading-row">
                <h2>Tus alertas</h2>
                <div class="chip" data-alert-count>0</div>
            </div>
            <div class="table-wrap">
                <table class="alerts-table" aria-live="polite">
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th>Umbral</th>
                            <th>Frecuencia</th>
                            <th>Estado</th>
                            <th class="actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="alertsTableBody">
                        <tr data-empty-row>
                            <td colspan="5">Aún no has creado alertas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="card alerts-card">
        <div class="card-heading-row">
            <h2>Notificaciones recientes</h2>
            <button class="btn btn-ghost" id="markAllEvents">Marcar todo como leído</button>
        </div>
        <div id="alertToastContainer" class="alert-toast-container" aria-live="assertive">
            <p class="empty-state" data-events-empty>No hay notificaciones pendientes.</p>
        </div>
    </section>
</div>

<div class="alert-detail-modal" id="alertDetailModal" aria-hidden="true">
    <div class="alert-detail-modal__backdrop" data-detail-close></div>
    <div class="alert-detail-modal__panel" role="dialog" aria-modal="true">
        <button class="alert-detail-modal__close" data-detail-close>&times;</button>
        <h3 id="detailTitle"></h3>
        <p id="detailSubtitle" class="detail-subtitle"></p>
        <dl class="detail-list">
            <div>
                <dt>Condición</dt>
                <dd id="detailCondition">—</dd>
            </div>
            <div>
                <dt>Valor actual</dt>
                <dd id="detailValue">—</dd>
            </div>
            <div>
                <dt>Sitio</dt>
                <dd id="detailSite">—</dd>
            </div>
            <div>
                <dt>Generado</dt>
                <dd id="detailTimestamp">—</dd>
            </div>
            <div>
                <dt>Estado</dt>
                <dd id="detailStatus">—</dd>
            </div>
        </dl>
        <div class="detail-actions">
            <button class="btn btn-primary" data-detail-mark>Marcar como leído</button>
            <button class="btn btn-ghost" data-detail-close>Cerrar</button>
        </div>
    </div>
</div>
@endsection
