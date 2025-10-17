import Plotly from "plotly.js-dist-min";
import {
  fetchPlot,
  applyMapping,
  normalisePlotError,
  plotIsEmpty,
} from "../utils/plot";
import { fetchDB, getSites } from "../utils/core";
import { canViewAllSites, currentUserSiteId } from "../utils/auth";

const DEFAULT_WIDGET_CATALOG = [
  {
    slug: "histogram_chart",
    name: "Histograma de corriente",
    kind: "chart",
    description: "Distribución de valores de corriente agregada por dispositivo.",
  },
  {
    slug: "scatter_chart",
    name: "Dispersión voltaje vs corriente",
    kind: "chart",
    description: "Relación entre corriente y voltaje promediada por hora.",
  },
  {
    slug: "timeseries_chart",
    name: "Serie temporal de potencia",
    kind: "chart",
    description: "Evolución de la potencia promedio en las últimas horas.",
  },
  {
    slug: "bar_chart",
    name: "Barras de energía",
    kind: "chart",
    description: "Energía acumulada por dispositivo en el periodo seleccionado.",
  },
  {
    slug: "heatmap_chart",
    name: "Heat map",
    kind: "chart",
    description: "Patrones temporales de potencia promedio.",
  },
  {
    slug: "devices_per_site",
    name: "Dispositivos por sitio",
    kind: "kpi",
    description: "Conteo total de dispositivos registrados en un sitio.",
    source_dataset: "devices",
  },
  {
    slug: "site_availability",
    name: "Disponibilidad del sitio (hoy)",
    kind: "kpi",
    description: "Porcentaje de availability del sitio para la fecha actual.",
    source_dataset: "site_daily_kpi",
  },
];

const DEFAULT_DASHBOARD_SLUGS = ["histogram_chart", "scatter_chart"];

const API_ROUTES = {
  dashboard: "/api/widgets/dashboard",
  catalog: "/api/widgets/catalog",
  attach: "/api/widgets/attach",
  detach: (id) => `/api/widgets/${id}`,
  update: (id) => `/api/widgets/${id}`,
};

const state = {
  dashboard: [],
  catalog: [],
  sites: [],
  sitesById: {},
  defaultSiteId: currentUserSiteId() ?? null,
  removedWidgetIds: new Set(),
};

const isSuperAdmin = canViewAllSites();
let isDirty = false;
let widgetsApiEnabled = true;
let isEditingMode = false;
let activeDragId = null;

function getSaveButton() {
  return document.getElementById("panel-save");
}

function setDirty(flag = true) {
  isDirty = flag;
  const saveBtn = getSaveButton();
  if (!saveBtn) return;
  if (flag) {
    saveBtn.disabled = false;
    saveBtn.textContent = "Guardar cambios";
  } else {
    saveBtn.disabled = true;
    saveBtn.textContent = "Cambios guardados";
  }
}

function applyEditModeUI() {
  const editButton = document.getElementById("panel-edit");
  if (editButton) {
    editButton.classList.toggle("is-active", isEditingMode);
    editButton.textContent = isEditingMode ? "Terminar orden" : "Reordenar";
  }
  const dashboardRoot = document.getElementById("panel-dashboard");
  const dashboardGrid = dashboardRoot?.querySelector(".widget-grid");
  dashboardRoot?.classList.toggle("is-editing", isEditingMode);
  dashboardGrid?.classList.toggle("is-editing", isEditingMode);
}

function toggleEditMode(force) {
  const nextMode = typeof force === "boolean" ? force : !isEditingMode;
  if (nextMode === isEditingMode) {
    applyEditModeUI();
    return;
  }
  isEditingMode = nextMode;
  applyEditModeUI();
  renderDashboard();
}

function syncDashboardOrderFromDom() {
  const dashboardRoot = document.getElementById("panel-dashboard");
  const dashboardGrid = dashboardRoot?.querySelector(".widget-grid");
  if (!dashboardGrid) return;
  const orderedIds = Array.from(
    dashboardGrid.querySelectorAll(".widget-card")
  ).map((card) => card.dataset.widgetId);
  if (!orderedIds.length) return;

  const widgetMap = new Map(state.dashboard.map((widget) => [String(widget.id), widget]));
  const reordered = orderedIds
    .map((id) => widgetMap.get(id))
    .filter(Boolean);

  if (reordered.length !== state.dashboard.length) {
    // append any widgets that were not found just in case
    widgetMap.forEach((widget, id) => {
      if (!orderedIds.includes(id)) {
        reordered.push(widget);
      }
    });
  }

  state.dashboard = reordered;
  setDirty(true);
}

function isWidgetsApiUrl(url) {
  return typeof url === "string" && url.startsWith("/api/widgets/");
}

function formatDateISO(date) {
  return date.toISOString().slice(0, 10);
}

function computeDateRange(days = 7) {
  const end = new Date();
  const start = new Date(end);
  start.setDate(start.getDate() - (days - 1));
  return {
    from: formatDateISO(start),
    to: formatDateISO(end),
  };
}

function formatNumber(value) {
  return new Intl.NumberFormat(undefined, {
    maximumFractionDigits: 0,
  }).format(value ?? 0);
}

function formatPercent(value) {
  if (value === null || value === undefined) return "-";
  return `${Number(value).toFixed(1)}%`;
}

const PLOT_MODEBAR_WHITELIST = ["resetScale2d", "toImage"];
const PLOT_MODEBAR_REMOVALS = [
  "zoom2d",
  "pan2d",
  "select2d",
  "lasso2d",
  "zoomIn2d",
  "zoomOut2d",
  "autoScale2d",
  "hoverClosestCartesian",
  "hoverCompareCartesian",
  "toggleSpikelines",
  "resetViews",
  "toggleHover",
  "hoverClosest3d",
  "hoverClosestGl2d",
  "hoverClosestPie",
  "hoverClosestGl3d",
  "toImage",
  "resetScale2d",
];

function sanitisePlotFigure(figure = {}) {
  if (!figure || typeof figure !== "object") {
    return { data: [], layout: {} };
  }
  if (!Array.isArray(figure.data)) {
    figure.data = [];
  }
  figure.layout = figure.layout && typeof figure.layout === "object" ? figure.layout : {};
  const { title } = figure.layout;
  if (title) {
    if (typeof title === "object" && title !== null) {
      figure.layout.title = { ...title, text: "" };
    } else {
      figure.layout.title = "";
    }
  }
  return figure;
}

function sanitisePlotConfig(config = {}) {
  const base = { ...config };
  base.responsive = true;
  base.displaylogo = false;
  base.displayModeBar = true;

  // Ensure we only remove buttons we don't need, keeping whitelist items.
  const existingRemovals = Array.isArray(base.modeBarButtonsToRemove)
    ? base.modeBarButtonsToRemove
    : [];
  const removalSet = new Set(existingRemovals);
  PLOT_MODEBAR_REMOVALS.forEach((button) => {
    if (!PLOT_MODEBAR_WHITELIST.includes(button)) {
      removalSet.add(button);
    }
  });
  // Add every button group from existing config if provided explicitly.
  if (Array.isArray(base.modeBarButtons)) {
    base.modeBarButtons.flat().forEach((button) => {
      if (!PLOT_MODEBAR_WHITELIST.includes(button)) {
        removalSet.add(button);
      }
    });
  }
  base.modeBarButtonsToRemove = Array.from(removalSet).filter(
    (button) => !PLOT_MODEBAR_WHITELIST.includes(button)
  );
  delete base.modeBarButtons;
  delete base.modeBarButtonsToAdd;

  base.toImageButtonOptions = {
    format: "png",
    filename: "panel-widget",
    scale: 2,
    ...base.toImageButtonOptions,
  };
  return base;
}

function normaliseCatalogPayload(rows = []) {
  return rows
    .map((row) => {
      if (!row) return null;
      if (typeof row === "string") {
        const fallback = DEFAULT_WIDGET_CATALOG.find((d) => d.slug === row);
        return fallback ? { ...fallback } : null;
      }
      const slug = row.slug || row.code;
      if (!slug) return null;
      const base = DEFAULT_WIDGET_CATALOG.find((d) => d.slug === slug) || {};
      return {
        slug,
        name: row.name || base.name || slug,
        kind: row.kind || base.kind || "chart",
        description: row.description || base.description || "",
        source_dataset: row.source_dataset || base.source_dataset || null,
      };
    })
    .filter(Boolean);
}

function normaliseWidgetPayload(payload) {
  if (!payload) return null;
  const definition = findDefinition(payload.slug || payload.widget_definition?.slug);
  const id = payload.id ?? payload.widget_id ?? `local-${Date.now()}`;
  const slug = payload.slug || payload.widget_definition?.slug;
  if (!slug) return null;
  const kind = payload.kind || definition?.kind || payload.widget_definition?.kind || "chart";
  const dataFilters = payload.data_filters || payload.filters || {};
  const title =
    payload.title || payload.name || definition?.name || "Widget personalizado";
  return {
    id,
    slug,
    kind,
    title,
    data_filters: {
      siteId:
        dataFilters.siteId ??
        dataFilters.site_id ??
        state.defaultSiteId ??
        currentUserSiteId() ??
        null,
      deviceId: dataFilters.deviceId ?? dataFilters.device_id ?? "ALL",
      dateRange: dataFilters.dateRange || computeDateRange(),
    },
    visual_config: payload.visual_config || payload.config || {},
  };
}

function findDefinition(slug) {
  if (!slug) return null;
  return (
    state.catalog.find((d) => d.slug === slug) ||
    DEFAULT_WIDGET_CATALOG.find((d) => d.slug === slug) ||
    null
  );
}

function createWidgetInstance(definition, overrides = {}) {
  const filters = {
    siteId: state.defaultSiteId ?? currentUserSiteId() ?? null,
    deviceId: "ALL",
    dateRange: computeDateRange(),
    ...(overrides.data_filters || {}),
  };
  return {
    id: `local-${Date.now()}-${Math.random().toString(16).slice(2)}`,
    slug: definition.slug,
    kind: definition.kind,
    title: definition.name,
    data_filters: filters,
    visual_config: overrides.visual_config || {},
  };
}

async function fetchJSON(url, options = {}) {
  if (!widgetsApiEnabled && isWidgetsApiUrl(url)) {
    const error = new Error("API de widgets deshabilitada");
    error.status = 503;
    error.payload = { message: error.message };
    throw error;
  }
  const response = await fetch(url, {
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content"),
    },
    ...options,
  });
  if (!response.ok) {
    const payload = await response.json().catch(() => ({}));
    const error = new Error(payload.message || "Request failed");
    error.payload = payload;
    error.status = response.status;
    if ((error.status === 404 || error.status === 501) && isWidgetsApiUrl(url)) {
      widgetsApiEnabled = false;
    }
    throw error;
  }
  return response.json();
}

async function ensureSites() {
  if (!isSuperAdmin) {
    const siteId = currentUserSiteId();
    if (siteId) {
      state.sites = [
        {
          site_id: String(siteId),
          site_name: `Sitio ${siteId}`,
        },
      ];
      state.sitesById[String(siteId)] = `Sitio ${siteId}`;
      state.defaultSiteId = String(siteId);
    }
    return;
  }
  if (state.sites.length) return;
  try {
    const response = await getSites();
    const rows = Array.isArray(response?.data)
      ? response.data
      : Array.isArray(response)
      ? response
      : [];
    state.sites = rows.map((row) => ({
      site_id: String(row.site_id ?? row.id),
      site_name: row.site_name || `Sitio ${row.site_id ?? row.id}`,
    }));
    state.sitesById = Object.fromEntries(
      state.sites.map((site) => [site.site_id, site.site_name])
    );
    if (!state.defaultSiteId && state.sites.length) {
      state.defaultSiteId = state.sites[0].site_id;
    }
  } catch (err) {
    console.warn("panels.js: unable to load site list", err);
  }
}

function getSiteLabel(siteId) {
  if (!siteId) return "Todos los sitios";
  return state.sitesById[String(siteId)] || `Sitio ${siteId}`;
}

async function loadCatalog() {
  try {
    const data = await fetchJSON(API_ROUTES.catalog);
    const rows = Array.isArray(data?.widgets) ? data.widgets : data;
    state.catalog = normaliseCatalogPayload(rows);
  } catch (err) {
    console.warn("panels.js: catalog endpoint unavailable, using defaults", err);
    if (!err || [404, 501, 503].includes(err.status)) {
      widgetsApiEnabled = false;
    }
    state.catalog = normaliseCatalogPayload(DEFAULT_WIDGET_CATALOG);
  }
  if (!state.catalog.length) {
    state.catalog = normaliseCatalogPayload(DEFAULT_WIDGET_CATALOG);
  }
  renderCatalog();
}

async function loadDashboard() {
  state.removedWidgetIds = new Set();
  try {
    const data = await fetchJSON(API_ROUTES.dashboard);
    const widgets = Array.isArray(data?.widgets) ? data.widgets : [];
    state.dashboard = widgets
      .map(normaliseWidgetPayload)
      .filter((widget) => !!widget);
  } catch (err) {
    console.warn("panels.js: dashboard endpoint unavailable", err);
    if (!err || [404, 501, 503].includes(err.status)) {
      widgetsApiEnabled = false;
    }
    state.dashboard = [];
  }
  if (!state.dashboard.length) {
    seedDefaultDashboard();
    setDirty(true);
  } else {
    setDirty(false);
  }
  renderDashboard();
}

function seedDefaultDashboard() {
  DEFAULT_DASHBOARD_SLUGS.forEach((slug) => {
    const def = findDefinition(slug);
    if (!def) return;
    const widget = createWidgetInstance(def);
    state.dashboard.push(widget);
  });
}

function renderCatalog() {
  const catalogRoot = document.getElementById("widget-catalog");
  const catalogList = catalogRoot?.querySelector(".widget-catalog");
  const catalogEmpty = document.getElementById("widget-catalog-empty");
  const searchInput = catalogRoot?.querySelector("#widget-search");
  const filterRadios = catalogRoot?.querySelectorAll('input[name="catalog-kind"]');
  if (!catalogList || !catalogEmpty) return;

  catalogList.innerHTML = "";
  if (!state.catalog.length) {
    catalogEmpty.hidden = false;
    return;
  }

  catalogEmpty.hidden = true;
  const query = (searchInput?.value || "").trim().toLowerCase();
  const kindFilter = Array.from(filterRadios || []).find((radio) => radio.checked)?.value || "all";

  const filtered = state.catalog.filter((definition) => {
    if (kindFilter !== "all" && definition.kind !== kindFilter) {
      return false;
    }
    if (!query) return true;
    const haystack = [
      definition.name,
      definition.description,
      definition.slug,
      definition.source_dataset,
    ]
      .filter(Boolean)
      .join(" ")
      .toLowerCase();
    return haystack.includes(query);
  });

  if (!filtered.length) {
    const empty = document.createElement("div");
    empty.className = "empty-state";
    empty.innerHTML = `
      <strong>No encontramos widgets que coincidan.</strong>
      <small>Prueba con otro término o cambia el filtro.</small>
    `;
    catalogList.appendChild(empty);
    return;
  }

  filtered.forEach((definition) => {
    const row = document.createElement("div");
    row.className = "widget-catalog__item";
    row.dataset.widgetSlug = definition.slug;
    row.innerHTML = `
      <div class="widget-catalog__header">
        <h4 class="widget-catalog__name">${definition.name}</h4>
        <span class="widget-card__meta">${
          definition.kind === "kpi" ? "KPI" : "Chart"
        }</span>
      </div>
      <p class="widget-catalog__description">${definition.description || ""}</p>
      <div class="widget-catalog__footer">
        <span class="widget-catalog__meta">${definition.source_dataset || "Directo"}</span>
        <button class="panel-button panel-button--primary" data-add>Agregar</button>
      </div>
    `;
    catalogList.appendChild(row);
  });
}

function renderDashboard() {
  const dashboardRoot = document.getElementById("panel-dashboard");
  const dashboardGrid = dashboardRoot?.querySelector(".widget-grid");
  const dashboardEmpty = document.getElementById("panel-dashboard-empty");
  if (!dashboardGrid || !dashboardEmpty) return;

  if (!state.dashboard.length) {
    dashboardGrid.innerHTML = "";
    dashboardEmpty.hidden = false;
    return;
  }

  dashboardEmpty.hidden = true;

  const existingCards = new Map(
    Array.from(dashboardGrid.querySelectorAll(".widget-card")).map((card) => [
      card.dataset.widgetId,
      card,
    ])
  );
  const orderedCards = [];

  state.dashboard.forEach((widget) => {
    const definition = findDefinition(widget.slug);
    if (!definition) return;
    const widgetId = String(widget.id);
    let card = existingCards.get(widgetId);
    if (card) {
      existingCards.delete(widgetId);
      updateWidgetCard(card, widget, definition);
    } else {
      card = renderWidgetCard(widget, definition);
    }
    orderedCards.push(card);
  });

  existingCards.forEach((card) => card.remove());

  const fragment = document.createDocumentFragment();
  orderedCards.forEach((card) => fragment.appendChild(card));
  dashboardGrid.innerHTML = "";
  dashboardGrid.appendChild(fragment);
  applyEditModeUI();
}

function applyWidgetCardKind(card, kind) {
  const isKpi = kind === "kpi";
  card.classList.toggle("widget-card--kpi", isKpi);
  card.classList.toggle("widget-card--chart", !isKpi);
}

function resetWidgetBody(container, kind) {
  if (!container) return;
  const existingChart = container.querySelector(".widget-chart");
  if (existingChart && Plotly && typeof Plotly.purge === "function") {
    try {
      Plotly.purge(existingChart);
    } catch (err) {
      console.debug("panels.js: unable to purge chart node", err);
    }
  }
  container.innerHTML = "";
  container.className = "widget-card__body";
  container.classList.add(kind === "chart" ? "widget-card__body--chart" : "widget-card__body--kpi");
  container.dataset.widgetKind = kind;
}

function renderWidgetCard(widget, definition) {
  const card = document.createElement("article");
  card.className = "widget-card";
  card.dataset.widgetId = String(widget.id);
  card.dataset.widgetSlug = widget.slug;
  card.dataset.widgetKind = definition.kind;
  applyWidgetCardKind(card, definition.kind);

  if (isEditingMode) {
    card.setAttribute("draggable", "true");
    card.classList.add("widget-card--draggable");
  }

  const header = document.createElement("header");
  header.className = "widget-card__header";

  const title = document.createElement("h3");
  title.className = "widget-card__title";
  title.textContent = widget.title || definition.name;

  const meta = document.createElement("span");
  meta.className = "widget-card__meta";
  meta.textContent = definition.kind === "kpi" ? "KPI" : "Chart";

  const removeBtn = document.createElement("button");
  removeBtn.className = "panel-button panel-button--ghost widget-card__remove";
  removeBtn.dataset.remove = "true";
  removeBtn.setAttribute("aria-label", "Quitar widget");
  removeBtn.innerHTML = '<span aria-hidden="true">&times;</span>';

  const headerInfo = document.createElement("div");
  headerInfo.className = "widget-card__header-info";
  headerInfo.appendChild(title);
  headerInfo.appendChild(meta);

  header.appendChild(headerInfo);
  header.appendChild(removeBtn);

  const body = document.createElement("div");
  body.className = "widget-card__body";

  card.appendChild(header);
  card.appendChild(body);

  if (definition.kind === "kpi") {
    renderKpiWidget(widget, definition, body);
  } else {
    renderChartWidget(widget, definition, body);
  }

  return card;
}

function updateWidgetCard(card, widget, definition) {
  if (!card) return;
  const kind = definition.kind;
  const body = card.querySelector(".widget-card__body");
  const previousSlug = card.dataset.widgetSlug;
  const previousKind = body?.dataset.widgetKind;

  card.dataset.widgetId = String(widget.id);
  card.dataset.widgetSlug = widget.slug;
  card.dataset.widgetKind = kind;
  applyWidgetCardKind(card, kind);
  if (isEditingMode) {
    card.setAttribute("draggable", "true");
    card.classList.add("widget-card--draggable");
  } else {
    card.removeAttribute("draggable");
    card.classList.remove("widget-card--draggable", "is-dragging", "widget-card--drop-target");
  }

  const expectedTitle = widget.title || definition.name;
  const titleNode = card.querySelector(".widget-card__title");
  if (titleNode && titleNode.textContent !== expectedTitle) {
    titleNode.textContent = expectedTitle;
  }

  const expectedMeta = kind === "kpi" ? "KPI" : "Chart";
  const metaNode = card.querySelector(".widget-card__meta");
  if (metaNode && metaNode.textContent !== expectedMeta) {
    metaNode.textContent = expectedMeta;
  }

  if (!body) return;

  const shouldRebuild = previousKind !== kind || previousSlug !== widget.slug;
  if (shouldRebuild) {
    if (kind === "kpi") {
      renderKpiWidget(widget, definition, body);
    } else {
      renderChartWidget(widget, definition, body);
    }
  }
}

function handleDragStart(event) {
  if (!isEditingMode) return;
  const card = event.target.closest(".widget-card");
  if (!card) return;
  activeDragId = card.dataset.widgetId;
  card.classList.add("is-dragging");
  event.dataTransfer.effectAllowed = "move";
  event.dataTransfer.setData("text/plain", activeDragId);
}

function handleDragOver(event) {
  if (!isEditingMode) return;
  event.preventDefault();
  const grid = event.currentTarget;
  const draggedId = activeDragId || event.dataTransfer.getData("text/plain");
  if (!grid || !draggedId) return;
  const draggedCard = grid.querySelector(`.widget-card[data-widget-id="${draggedId}"]`);
  if (!draggedCard) return;

  const targetCard = event.target.closest(".widget-card");
  grid.querySelectorAll(".widget-card--drop-target").forEach((el) => {
    if (el !== targetCard) el.classList.remove("widget-card--drop-target");
  });

  if (!targetCard) {
    grid.appendChild(draggedCard);
    return;
  }

  if (targetCard === draggedCard) {
    return;
  }

  const rect = targetCard.getBoundingClientRect();
  const halfwayY = rect.top + rect.height / 2;
  const halfwayX = rect.left + rect.width / 2;
  const before = event.clientY < halfwayY || event.clientX < halfwayX;

  targetCard.classList.add("widget-card--drop-target");
  if (before) {
    grid.insertBefore(draggedCard, targetCard);
  } else {
    grid.insertBefore(draggedCard, targetCard.nextSibling);
  }
}

function handleDragLeave(event) {
  if (!isEditingMode) return;
  const card = event.target.closest(".widget-card");
  card?.classList.remove("widget-card--drop-target");
}

function handleDrop(event) {
  if (!isEditingMode) return;
  event.preventDefault();
  const grid = event.currentTarget;
  grid.querySelectorAll(".widget-card--drop-target").forEach((el) =>
    el.classList.remove("widget-card--drop-target")
  );
  const draggedCard = grid.querySelector(`.widget-card[data-widget-id="${activeDragId}"]`);
  draggedCard?.classList.remove("is-dragging");
  syncDashboardOrderFromDom();
  activeDragId = null;
}

function handleDragEnd(event) {
  if (!isEditingMode) return;
  const card = event.target.closest(".widget-card");
  card?.classList.remove("is-dragging");
  const grid = document.querySelector("#panel-dashboard .widget-grid");
  grid?.querySelectorAll(".widget-card--drop-target").forEach((el) =>
    el.classList.remove("widget-card--drop-target")
  );
  activeDragId = null;
}

function renderKpiWidget(widget, definition, container) {
  if (widget.slug === "devices_per_site") {
    renderDeviceCountWidget(widget, container);
    return;
  }
  if (widget.slug === "site_availability") {
    renderAvailabilityWidget(widget, container);
    return;
  }
  resetWidgetBody(container, "kpi");
  container.innerHTML = `<div class="empty-state"><strong>Pendiente de implementación</strong><small>Este KPI estará disponible próximamente.</small></div>`;
}

function renderDeviceCountWidget(widget, container) {
  resetWidgetBody(container, "kpi");

  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__kpi-wrapper";

  if (isSuperAdmin && state.sites.length) {
    const controls = document.createElement("div");
    controls.className = "widget-card__controls";
    const label = document.createElement("label");
    label.textContent = "Sitio";
    const select = document.createElement("select");
    state.sites.forEach((site) => {
      const option = document.createElement("option");
      option.value = site.site_id;
      option.textContent = site.site_name;
      if (String(widget.data_filters?.siteId ?? state.defaultSiteId) === site.site_id) {
        option.selected = true;
      }
      select.appendChild(option);
    });
    select.addEventListener("change", async (event) => {
      await updateWidgetFilters(widget, { siteId: event.target.value });
      renderDeviceCountWidget(widget, container);
    });
    controls.appendChild(label);
    controls.appendChild(select);
    wrapper.appendChild(controls);
  }

  const metric = document.createElement("div");
  metric.className = "widget-card__metric";
  metric.textContent = "-";

  const helper = document.createElement("small");
  helper.textContent = "Dispositivos registrados";

  metric.appendChild(helper);
  wrapper.appendChild(metric);
  container.appendChild(wrapper);

  const filters = widget.data_filters || {};
  const siteId = filters.siteId || state.defaultSiteId || currentUserSiteId();

  const requestBody = {
    table: "devices",
    aggregation: [
      {
        group_by: ["site_id"],
        aggregations: {
          device_id: ["count"],
        },
      },
    ],
  };
  if (siteId && siteId !== "ALL") {
    requestBody.filter_map = {
      site_id: "=" + siteId,
    };
  }

  fetchDB(requestBody)
    .then((response) => {
      const rows = Array.isArray(response?.data)
        ? response.data
        : Array.isArray(response)
        ? response
        : [];
      let record = null;
      if (siteId && siteId !== "ALL") {
        record = rows.find((row) => String(row.site_id) === String(siteId));
      }
      if (!record && rows.length) {
        record = rows[0];
      }
      const count = record?.device_id_count ?? record?.device_id_count ?? 0;
      metric.firstChild.textContent = formatNumber(count);
      metric.setAttribute("aria-label", `Total de dispositivos ${count}`);
    })
    .catch((err) => {
      console.error("panels.js: device count failed", err);
      metric.firstChild.textContent = "-";
      helper.textContent = "No fue posible obtener el conteo";
    });
}

function renderAvailabilityWidget(widget, container) {
  resetWidgetBody(container, "kpi");

  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__kpi-wrapper";

  if (isSuperAdmin && state.sites.length) {
    const controls = document.createElement("div");
    controls.className = "widget-card__controls";
    const label = document.createElement("label");
    label.textContent = "Sitio";
    const select = document.createElement("select");
    state.sites.forEach((site) => {
      const option = document.createElement("option");
      option.value = site.site_id;
      option.textContent = site.site_name;
      if (String(widget.data_filters?.siteId ?? state.defaultSiteId) === site.site_id) {
        option.selected = true;
      }
      select.appendChild(option);
    });
    select.addEventListener("change", async (event) => {
      await updateWidgetFilters(widget, { siteId: event.target.value });
      renderAvailabilityWidget(widget, container);
    });
    controls.appendChild(label);
    controls.appendChild(select);
    wrapper.appendChild(controls);
  }

  const metric = document.createElement("div");
  metric.className = "widget-card__metric";
  metric.textContent = "-";
  const helper = document.createElement("small");
  helper.textContent = "Availability hoy";
  metric.appendChild(helper);
  wrapper.appendChild(metric);
  container.appendChild(wrapper);

  const filters = widget.data_filters || {};
  const siteId = filters.siteId || state.defaultSiteId || currentUserSiteId();
  const today = formatDateISO(new Date());

  const requestBody = {
    table: "site_daily_kpi",
    filter_map: {
      kpi_date: "=" + today,
    },
    aggregation: [
      {
        group_by: ["site_id"],
        aggregations: {
          availability_pct: ["avg"],
        },
      },
    ],
  };
  if (siteId && siteId !== "ALL") {
    requestBody.filter_map.site_id = "=" + siteId;
  }

  fetchDB(requestBody)
    .then((response) => {
      const rows = Array.isArray(response?.data)
        ? response.data
        : Array.isArray(response)
        ? response
        : [];
      let record = null;
      if (siteId && siteId !== "ALL") {
        record = rows.find((row) => String(row.site_id) === String(siteId));
      }
      if (!record && rows.length) {
        record = rows[0];
      }
      const pct = record?.availability_pct_avg ?? record?.availability_pct ?? null;
      metric.firstChild.textContent = pct === null ? "-" : formatPercent(pct);
      metric.setAttribute("aria-label", `Disponibilidad ${metric.firstChild.textContent}`);
    })
    .catch((err) => {
      console.error("panels.js: availability fetch failed", err);
      helper.textContent = "No fue posible obtener la disponibilidad";
    });
}

function renderChartWidget(widget, definition, container) {
  resetWidgetBody(container, "chart");

  const filters = {
    siteId: widget.data_filters?.siteId || state.defaultSiteId || currentUserSiteId(),
    deviceId: widget.data_filters?.deviceId || "ALL",
    dateRange: widget.data_filters?.dateRange || computeDateRange(),
  };

  const requestBody = buildChartRequest(widget.slug, filters);
  if (!requestBody) {
    container.innerHTML = `
      <div class="empty-state">
        <strong>Widget en desarrollo</strong>
        <small>Este tipo de gráfica aún no está disponible en el panel.</small>
      </div>`;
    return;
  }

  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__chart-wrapper";
  container.appendChild(wrapper);

  if (isSuperAdmin && state.sites.length) {
    const controls = document.createElement("div");
    controls.className = "widget-card__controls widget-card__controls--chart";
    const label = document.createElement("label");
    label.textContent = "Sitio";
    const select = document.createElement("select");
    state.sites.forEach((site) => {
      const option = document.createElement("option");
      option.value = site.site_id;
      option.textContent = site.site_name;
      if (String(filters.siteId ?? state.defaultSiteId) === site.site_id) {
        option.selected = true;
      }
      select.appendChild(option);
    });
    select.addEventListener("change", async (event) => {
      await updateWidgetFilters(widget, { siteId: event.target.value });
      renderChartWidget(widget, definition, container);
    });
    controls.appendChild(label);
    controls.appendChild(select);
    wrapper.appendChild(controls);
  }

  const chartNode = document.createElement("div");
  chartNode.className = "widget-chart";
  chartNode.id = `widget-chart-${widget.id}`;
  const loadingIndicator = document.createElement("div");
  loadingIndicator.className = "widget-card__body--loading";
  loadingIndicator.textContent = "Cargando datos…";
  chartNode.appendChild(loadingIndicator);

  wrapper.appendChild(chartNode);

  const removeLoading = () => {
    if (loadingIndicator.parentNode) {
      loadingIndicator.remove();
    }
  };

  fetchPlot(requestBody)
    .then(({ figure, config, mapping }) => {
      if (!container.isConnected) {
        removeLoading();
        return;
      }
      applyMapping(figure, mapping);
      const cleanedFigure = sanitisePlotFigure(figure);
      if (plotIsEmpty(cleanedFigure)) {
        removeLoading();
        if (Plotly && typeof Plotly.purge === "function") {
          Plotly.purge(chartNode);
        }
        chartNode.innerHTML = `
          <div class="empty-state">
            <strong>Sin datos</strong>
            <small>No hay información disponible para el periodo seleccionado.</small>
          </div>`;
        return;
      }
      removeLoading();
      const plotConfig = sanitisePlotConfig(config);
      return Plotly.react(chartNode, cleanedFigure.data, cleanedFigure.layout, plotConfig);
    })
    .catch((err) => {
      removeLoading();
      if (!container.isConnected) return;
      console.error("panels.js: chart fetch failed", err);
      const { message } = normalisePlotError(err);
      if (Plotly && typeof Plotly.purge === "function") {
        Plotly.purge(chartNode);
      }
      chartNode.innerHTML = `
        <div class="empty-state">
          <strong>No se pudo cargar el gráfico</strong>
          <small>${message}</small>
        </div>`;
    });
}

function buildChartRequest(slug, filters) {
  const { siteId, deviceId, dateRange } = filters;
  const filter_map = {
    measurement_time: `[${dateRange.from} 00:00:00, ${dateRange.to} 23:59:59]`,
  };
  if (siteId && siteId !== "ALL") {
    filter_map.site_id = "=" + siteId;
  }
  if (deviceId && deviceId !== "ALL") {
    filter_map.device_id = "=" + deviceId;
  }

  switch (slug) {
    case "histogram_chart":
      return {
        table: "measurements",
        filter_map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              current_a: ["avg"],
            },
            time_window: "H",
            time_column: "measurement_time",
          },
        ],
        chart: {
          chart_type: "histogram",
          x: "current_a_avg",
          style: {
            nbins: 30,
            color: "device_id",
          },
        },
      };
    case "scatter_chart":
      return {
        table: "measurements",
        filter_map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              current_a: ["avg"],
              voltage_v: ["avg"],
            },
            time_window: "H",
            time_column: "measurement_time",
          },
        ],
        chart: {
          chart_type: "scatter",
          x: "current_a_avg",
          y: "voltage_v_avg",
          style: { color: "device_id", opacity: 0.75 },
        },
      };
    case "timeseries_chart":
      return {
        table: "measurements",
        filter_map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              power_w: ["avg"],
            },
            time_window: "H",
            time_column: "measurement_time",
          },
        ],
        chart: {
          chart_type: "line",
          x: "measurement_time",
          y: "power_w_avg",
          style: { color: "device_id" },
        },
      };
    case "bar_chart":
      return {
        table: "measurements",
        filter_map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              energy_wh: ["sum"],
            },
            time_window: "D",
            time_column: "measurement_time",
          },
        ],
        chart: {
          chart_type: "bar",
          x: "device_id",
          y: "energy_wh_sum",
          style: { orientation: "v", color: "device_id" },
        },
      };
    case "heatmap_chart":
      return {
        table: "measurements",
        filter_map,
        aggregation: [
          {
            aggregations: {
              power_w: ["avg"],
            },
            time_window: "H",
            time_column: "measurement_time",
          },
        ],
        chart: {
          chart_type: "heatmap",
          x: "hour",
          y: "weekday",
          z: "power_w_avg",
        },
      };
    default:
      return null;
  }
}

async function updateWidgetFilters(widget, partial) {
  widget.data_filters = {
    ...(widget.data_filters || {}),
    ...partial,
  };
  setDirty(true);
  if (typeof widget.id === "number") {
    try {
      await fetchJSON(API_ROUTES.update(widget.id), {
        method: "PATCH",
        body: JSON.stringify({ data_filters: widget.data_filters }),
      });
    } catch (err) {
      console.warn("panels.js: unable to persist widget filters", err);
      if (err.status === 404 || err.status === 503) {
        widgetsApiEnabled = false;
      }
    }
  }
}

async function addWidget(slug) {
  const definition = findDefinition(slug);
  if (!definition) return;

  let widget = createWidgetInstance(definition);
  if (widgetsApiEnabled) {
    try {
      const response = await fetchJSON(API_ROUTES.attach, {
        method: "POST",
        body: JSON.stringify({ slug }),
      });
      if (response?.widget) {
        const normalised = normaliseWidgetPayload(response.widget);
        if (normalised) {
          widget = normalised;
        }
      }
  } catch (err) {
    console.warn("panels.js: attach endpoint unavailable", err);
    if (!err || [404, 501, 503].includes(err.status)) {
      widgetsApiEnabled = false;
    }
  }
  }

  state.dashboard.push(widget);
  renderDashboard();
  setDirty(true);
}

async function removeWidget(widgetId) {
  const numericId = Number(widgetId);
  if (!Number.isNaN(numericId)) {
    if (!(state.removedWidgetIds instanceof Set)) {
      state.removedWidgetIds = new Set();
    }
    state.removedWidgetIds.add(numericId);
  }
  state.dashboard = state.dashboard.filter(
    (entry) => String(entry.id) !== String(widgetId)
  );
  renderDashboard();
  setDirty(true);
}

function registerEvents() {
  const dashboardRoot = document.getElementById("panel-dashboard");
  const dashboardGrid = dashboardRoot?.querySelector(".widget-grid");
  const catalogRoot = document.getElementById("widget-catalog");
  const catalogList = catalogRoot?.querySelector(".widget-catalog");
  const catalogSearch = catalogRoot?.querySelector("#widget-search");
  const catalogFilters = catalogRoot?.querySelectorAll('input[name="catalog-kind"]');
  const drawer = document.getElementById("widget-drawer");
  const drawerToggle = document.getElementById("widget-drawer-toggle");
  const drawerClose = document.getElementById("widget-drawer-close");
  const editButton = document.getElementById("panel-edit");
  const saveButton = getSaveButton();

  dashboardGrid?.addEventListener("click", (event) => {
    const removeButton = event.target.closest("[data-remove]");
    if (!removeButton) return;
    const card = removeButton.closest(".widget-card");
    if (!card) return;
    removeWidget(card.dataset.widgetId);
  });

  dashboardGrid?.addEventListener("dragstart", handleDragStart);
  dashboardGrid?.addEventListener("dragover", handleDragOver);
  dashboardGrid?.addEventListener("dragleave", handleDragLeave);
  dashboardGrid?.addEventListener("drop", handleDrop);
  dashboardGrid?.addEventListener("dragend", handleDragEnd);

  catalogList?.addEventListener("click", (event) => {
    const addButton = event.target.closest("[data-add]");
    if (!addButton) return;
    const row = addButton.closest(".widget-catalog__item");
    if (!row) return;
    const slug = row.dataset.widgetSlug;
    addWidget(slug);
  });

  catalogSearch?.addEventListener("input", () => renderCatalog());
  catalogFilters?.forEach((radio) =>
    radio.addEventListener("change", () => renderCatalog())
  );

  drawerToggle?.addEventListener("click", () => {
    drawer?.classList.add("is-open");
  });

  drawerClose?.addEventListener("click", () => {
    drawer?.classList.remove("is-open");
  });

  drawer?.addEventListener("click", (event) => {
    if (event.target === drawer) {
      drawer.classList.remove("is-open");
    }
  });

  editButton?.addEventListener("click", () => {
    toggleEditMode();
  });

  saveButton?.addEventListener("click", () => {
    if (!isDirty) return;
    saveDashboard();
  });
}

async function init() {
  await ensureSites();
  await loadCatalog();
  await loadDashboard();
  registerEvents();
}

document.addEventListener("DOMContentLoaded", init);

async function saveDashboard() {
  const saveBtn = getSaveButton();
  if (!saveBtn) return;
  saveBtn.disabled = true;
  const originalLabel = saveBtn.textContent;
  saveBtn.textContent = "Guardando…";
  try {
    if (!widgetsApiEnabled) {
      renderDashboard();
      setDirty(false);
      saveBtn.textContent = "Sin backend";
      setTimeout(() => {
        if (!isDirty) {
          saveBtn.textContent = "Guardar cambios";
          saveBtn.disabled = true;
        }
      }, 1500);
      return;
    }

    const pendingRemovals =
      state.removedWidgetIds instanceof Set
        ? Array.from(state.removedWidgetIds)
        : [];
    if (pendingRemovals.length) {
      let removalFailed = false;
      for (const id of pendingRemovals) {
        try {
          await fetchJSON(API_ROUTES.detach(id), { method: "DELETE" });
          state.removedWidgetIds.delete(id);
        } catch (err) {
          console.warn("panels.js: unable to delete widget during save", err);
          if (!err || [404, 501, 503].includes(err.status)) {
            widgetsApiEnabled = false;
            removalFailed = true;
            break;
          }
          throw err;
        }
      }
      if (removalFailed || !widgetsApiEnabled) {
        renderDashboard();
        setDirty(false);
        saveBtn.textContent = "Sin backend";
        setTimeout(() => {
          if (!isDirty) {
            saveBtn.textContent = "Guardar cambios";
            saveBtn.disabled = true;
          }
        }, 1500);
        return;
      }
    }

    for (let i = 0; i < state.dashboard.length; i += 1) {
      const widget = state.dashboard[i];
      widget.position_index = i;
      const body = {
        position_index: i,
        data_filters: widget.data_filters,
        visual_config: widget.visual_config,
      };
      if (typeof widget.id === "number") {
        await fetchJSON(API_ROUTES.update(widget.id), {
          method: "PATCH",
          body: JSON.stringify(body),
        });
      } else {
        const payload = { ...body, slug: widget.slug };
        const response = await fetchJSON(API_ROUTES.attach, {
          method: "POST",
          body: JSON.stringify(payload),
        });
        const normalised = normaliseWidgetPayload(response?.widget);
        if (normalised) {
          state.dashboard[i] = normalised;
        }
      }
    }
    renderDashboard();
    state.removedWidgetIds = new Set();
    if (isEditingMode) {
      toggleEditMode(false);
    }
    setDirty(false);
    saveBtn.textContent = "Cambios guardados";
    setTimeout(() => {
      if (!isDirty) {
        saveBtn.textContent = "Guardar cambios";
        saveBtn.disabled = true;
      }
    }, 2000);
  } catch (err) {
    console.error("panels.js: unable to save dashboard", err);
    const { message } = normalisePlotError(err);
    saveBtn.textContent = "Error al guardar";
    alert(`No se pudo guardar el panel: ${message}`);
    saveBtn.disabled = false;
    if (err.status === 404 || err.status === 503) {
      widgetsApiEnabled = false;
    }
  } finally {
    if (isDirty) {
      saveBtn.textContent = originalLabel;
      saveBtn.disabled = false;
    }
  }
}
