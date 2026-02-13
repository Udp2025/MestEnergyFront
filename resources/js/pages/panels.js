import Plotly from "plotly.js-dist-min";
import {
  fetchPlot,
  applyMapping,
  normalisePlotError,
  plotIsEmpty,
  csrfToken,
} from "../utils/plot";
import { fetchDB, getSites, getDevices } from "../utils/core";
import { canViewAllSites, currentUserSiteId } from "../utils/auth";

const DEFAULT_WIDGET_CATALOG = [
  {
    slug: "forecast_power_chart",
    name: "Pronóstico de potencia (7 días)",
    kind: "chart",
    description:
      "Predicción diaria de la potencia para la próxima semana con intervalo de confianza.",
  },
  {
    slug: "anomaly_detection_chart",
    name: "Detección de anomalías (24 h)",
    kind: "chart",
    description:
      "Identificación de anomalías en las últimas 24 horas usando histórico extendido.",
  },
  {
    slug: "histogram_chart",
    name: "Histograma de corriente",
    kind: "chart",
    description:
      "Distribución de valores de corriente agregada por dispositivo.",
  },
  {
    slug: "histogram_today_chart",
    name: "Histograma de corriente (hoy)",
    kind: "chart",
    description:
      "Distribución de corriente por dispositivo durante el día actual.",
    source_dataset: "measurements",
  },
  {
    slug: "histogram_month_chart",
    name: "Histograma de corriente (mes)",
    kind: "chart",
    description:
      "Distribución de corriente acumulada en el mes actual (promedio diario).",
    source_dataset: "measurements",
  },
  {
    slug: "scatter_chart",
    name: "Dispersión voltaje vs corriente",
    kind: "chart",
    description: "Relación entre corriente y voltaje promediada por hora.",
  },
  {
    slug: "scatter_today_chart",
    name: "Voltaje vs corriente (hoy)",
    kind: "chart",
    description: "Dispersión de voltaje y corriente del día actual.",
    source_dataset: "measurements",
  },
  {
    slug: "scatter_month_chart",
    name: "Voltaje vs corriente (mes)",
    kind: "chart",
    description:
      "Dispersión promedio diaria de voltaje y corriente del mes en curso.",
    source_dataset: "measurements",
  },
  {
    slug: "timeseries_chart",
    name: "Serie temporal de potencia",
    kind: "chart",
    description: "Evolución de la potencia promedio en las últimas horas.",
  },
  {
    slug: "timeseries_today_chart",
    name: "Potencia promedio (hoy)",
    kind: "chart",
    description: "Serie temporal horaria de la potencia del día actual.",
    source_dataset: "measurements",
  },
  {
    slug: "timeseries_month_chart",
    name: "Potencia promedio (mes)",
    kind: "chart",
    description: "Serie temporal diaria de la potencia del mes actual.",
    source_dataset: "measurements",
  },
  {
    slug: "bar_chart",
    name: "Barras de energía",
    kind: "chart",
    description:
      "Energía acumulada por dispositivo en el periodo seleccionado.",
  },
  {
    slug: "bar_today_chart",
    name: "Energía por dispositivo (hoy)",
    kind: "chart",
    description: "Energía acumulada por dispositivo durante el día actual.",
    source_dataset: "measurements",
  },
  {
    slug: "bar_month_chart",
    name: "Energía por dispositivo (mes)",
    kind: "chart",
    description: "Energía acumulada por dispositivo en el mes en curso.",
    source_dataset: "measurements",
  },
  {
    slug: "heatmap_chart",
    name: "Heat map",
    kind: "chart",
    description: "Patrones temporales de potencia promedio.",
  },
  {
    slug: "heatmap_today_chart",
    name: "Mapa de calor (hoy)",
    kind: "chart",
    description: "Mapa de calor de potencia por hora durante el día actual.",
    source_dataset: "measurements",
  },
  {
    slug: "heatmap_month_chart",
    name: "Mapa de calor (mes)",
    kind: "chart",
    description: "Mapa de calor diario de potencia durante el mes actual.",
    source_dataset: "measurements",
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
  {
    slug: "energy_today_kpi",
    name: "Energía generada hoy",
    kind: "kpi",
    description: "Energía total acumulada del sitio en el día actual.",
    source_dataset: "site_daily_kpi",
  },
  {
    slug: "peak_power_kpi",
    name: "Potencia pico del día",
    kind: "kpi",
    description:
      "Valor máximo de potencia registrado para el sitio durante el día.",
    source_dataset: "site_daily_kpi",
  },
  {
    slug: "load_factor_kpi",
    name: "Load factor diario",
    kind: "kpi",
    description:
      "Relación entre la energía real generada y la energía máxima posible (load factor).",
    source_dataset: "site_daily_kpi",
  },
  {
    slug: "pf_compliance_kpi",
    name: "Cumplimiento factor de potencia",
    kind: "kpi",
    description:
      "Porcentaje de cumplimiento del factor de potencia objetivo en el sitio.",
    source_dataset: "site_daily_kpi",
  },
  {
    slug: "data_freshness_kpi",
    name: "Latencia de datos",
    kind: "kpi",
    description:
      "Minutos transcurridos desde la última actualización de datos del sitio.",
    source_dataset: "site_daily_kpi",
  },
  {
    slug: "active_devices_kpi",
    name: "Dispositivos activos",
    kind: "kpi",
    description: "Número de dispositivos reportando datos en la última hora.",
    source_dataset: "site_hourly_kpi",
  },
  {
    slug: "energy_last7_chart",
    name: "Energía últimos 7 días",
    kind: "chart",
    description: "Tendencia de energía diaria generada en la última semana.",
    source_dataset: "site_daily_kpi",
  },
  {
    slug: "power_factor_trend_chart",
    name: "Tendencia factor de potencia",
    kind: "chart",
    description: "Seguimiento del factor de potencia promedio diario.",
    source_dataset: "site_daily_kpi",
  },
  {
    slug: "availability_trend_chart",
    name: "Disponibilidad horaria",
    kind: "chart",
    description: "Disponibilidad porcentual por hora durante los últimos días.",
    source_dataset: "site_hourly_kpi",
  },
  {
    slug: "device_energy_rank_chart",
    name: "Ranking energía por dispositivo",
    kind: "chart",
    description:
      "Comparativo de energía generada por dispositivo en el periodo seleccionado.",
    source_dataset: "device_daily_kpi",
  },
  {
    slug: "ingestion_lag_chart",
    name: "Latencia de ingesta",
    kind: "chart",
    description:
      "Latencia promedio de los procesos de ingesta de datos recientes.",
    source_dataset: "ingestion_run_kpi",
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
const DEVICE_FILTER_WIDGETS = new Set([
  "forecast_power_chart",
  "anomaly_detection_chart",
]);
const deviceCache = new Map();

function resolveSiteId(widget, options = {}) {
  const allowAll = options.allowAll !== false;
  const candidate = widget?.data_filters?.siteId;
  if (candidate && (allowAll || candidate !== "ALL")) {
    return candidate;
  }
  return state.defaultSiteId ?? currentUserSiteId() ?? null;
}
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

  const widgetMap = new Map(
    state.dashboard.map((widget) => [String(widget.id), widget])
  );
  const reordered = orderedIds.map((id) => widgetMap.get(id)).filter(Boolean);

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

function defaultRangeForSlug(slug) {
  switch (slug) {
    case "scatter_chart":
      return computeDateRange(30); // voltaje vs corriente: último mes
    default:
      return computeDateRange();
  }
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

function formatEnergy(valueWh) {
  if (valueWh === null || valueWh === undefined) return "-";
  const absValue = Math.abs(Number(valueWh));
  if (absValue >= 1_000_000) {
    return `${(valueWh / 1_000_000).toFixed(2)} MWh`;
  }
  if (absValue >= 1_000) {
    return `${(valueWh / 1_000).toFixed(1)} kWh`;
  }
  return `${Number(valueWh).toFixed(0)} Wh`;
}

function formatMinutes(value) {
  if (value === null || value === undefined) return "-";
  if (value < 60) return `${value.toFixed(0)} min`;
  const hours = value / 60;
  if (hours < 24) return `${hours.toFixed(1)} h`;
  const days = hours / 24;
  return `${days.toFixed(1)} d`;
}

function computePastHours(hours = 24) {
  const end = new Date();
  const start = new Date(end.getTime() - hours * 60 * 60 * 1000);
  const to = end.toISOString().slice(0, 19).replace("T", " ");
  const from = start.toISOString().slice(0, 19).replace("T", " ");
  return { from, to };
}

function appendSiteSelector(container, widget, onChange) {
  if (!isSuperAdmin || !state.sites.length) return null;
  const controls = document.createElement("div");
  controls.className = "widget-card__controls";
  const label = document.createElement("label");
  label.textContent = "Sitio";
  const select = document.createElement("select");
  const resolvedSiteId = resolveSiteId(widget);
  state.sites.forEach((site) => {
    const option = document.createElement("option");
    option.value = site.site_id;
    option.textContent = site.site_name;
    if (resolvedSiteId && String(resolvedSiteId) === site.site_id) {
      option.selected = true;
    }
    select.appendChild(option);
  });
  select.addEventListener("change", async (event) => {
    await updateWidgetFilters(widget, { siteId: event.target.value });
    onChange(event.target.value);
  });
  controls.appendChild(label);
  controls.appendChild(select);
  container.appendChild(controls);
  return controls;
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

async function fetchChart(config) {
  const endpoint =
    config && typeof config === "object" && config.endpoint
      ? config.endpoint
      : "/charts/plot";
  const payload =
    config &&
    typeof config === "object" &&
    Object.prototype.hasOwnProperty.call(config, "payload")
      ? config.payload
      : config;

  if (endpoint === "/charts/plot") {
    return fetchPlot(payload);
  }

  const response = await fetch(endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken(),
      Accept: "application/json",
    },
    body: JSON.stringify(payload ?? {}),
  });

  if (!response.ok) {
    let errorPayload;
    try {
      errorPayload = await response.json();
    } catch (_) {
      errorPayload = await response.text();
    }
    const error = new Error("Plot API request failed");
    error.status = response.status;
    error.payload = errorPayload;
    error.isPlotError = true;
    throw error;
  }

  return response.json();
}

function sanitisePlotFigure(figure = {}) {
  if (!figure || typeof figure !== "object") {
    return { data: [], layout: {} };
  }
  if (!Array.isArray(figure.data)) {
    figure.data = [];
  }
  figure.layout =
    figure.layout && typeof figure.layout === "object" ? figure.layout : {};
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
  const definition = findDefinition(
    payload.slug || payload.widget_definition?.slug
  );
  const id = payload.id ?? payload.widget_id ?? `local-${Date.now()}`;
  const slug = payload.slug || payload.widget_definition?.slug;
  if (!slug) return null;
  const kind =
    payload.kind ||
    definition?.kind ||
    payload.widget_definition?.kind ||
    "chart";
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

const MAX_SUMMARY_LENGTH = 240;
const FALLBACK_SUMMARY = {
  chart: "Gráfica que resume tendencias energéticas recientes.",
  kpi: "Indicador clave del estado operativo del sitio.",
};

function normaliseSummaryText(text) {
  if (!text) return "";
  const normalized = String(text).replace(/\s+/g, " ").trim();
  if (!normalized) return "";
  if (normalized.length <= MAX_SUMMARY_LENGTH) {
    return normalized;
  }
  return `${normalized.slice(0, MAX_SUMMARY_LENGTH - 1).trim()}…`;
}

function getWidgetSummary(widget = {}, definition = {}) {
  const candidate =
    widget.visual_config?.summary ||
    widget.summary ||
    definition.description ||
    widget.description ||
    null;
  if (candidate) {
    return normaliseSummaryText(candidate);
  }
  return definition.kind === "kpi"
    ? FALLBACK_SUMMARY.kpi
    : FALLBACK_SUMMARY.chart;
}

function createWidgetInfoBadge(widget, definition) {
  const summary = getWidgetSummary(widget, definition);
  if (!summary) return null;
  const info = document.createElement("span");
  info.className = "widget-card__info";
  info.dataset.summary = summary;
  info.setAttribute("title", summary);
  info.setAttribute("aria-label", summary);
  info.setAttribute("role", "note");
  info.textContent = "i";
  return info;
}

function syncInfoBadge(card, widget, definition) {
  const actions = card.querySelector(".widget-card__header-actions");
  if (!actions) return;
  let info = actions.querySelector(".widget-card__info");
  if (!info) {
    info = createWidgetInfoBadge(widget, definition);
    if (info) {
      actions.insertBefore(info, actions.firstChild || null);
    }
    return;
  }
  const summary = getWidgetSummary(widget, definition);
  if (!summary) {
    info.remove();
    return;
  }
  info.dataset.summary = summary;
  info.setAttribute("title", summary);
  info.setAttribute("aria-label", summary);
}

function createWidgetInstance(definition, overrides = {}) {
  const filters = {
    siteId: state.defaultSiteId ?? currentUserSiteId() ?? null,
    deviceId: "ALL",
    dateRange: computeDateRange(),
    ...(overrides.data_filters || {}),
  };
  if (/_today_/i.test(definition.slug) || /_today$/.test(definition.slug)) {
    const today = formatDateISO(new Date());
    filters.dateRange = { from: today, to: today };
  } else if (
    /_month_/i.test(definition.slug) ||
    /_month$/.test(definition.slug)
  ) {
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    filters.dateRange = {
      from: formatDateISO(start),
      to: formatDateISO(end),
    };
  }
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
    const payload = await response
      .json()
      .catch(() => ({ message: response.statusText }));
    const error = new Error(payload.message || "Request failed");
    error.payload = payload;
    error.status = response.status;
    if (
      (error.status === 404 || error.status === 501) &&
      isWidgetsApiUrl(url)
    ) {
      widgetsApiEnabled = false;
    }
    throw error;
  }
  if (response.status === 204) {
    return null;
  }
  const raw = await response.text();
  const text = raw.trim();
  if (!text) {
    return null;
  }
  try {
    return JSON.parse(text);
  } catch (err) {
    console.warn("fetchJSON: unable to parse response", err, { url });
    return null;
  }
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

async function loadDevicesForSite(siteId) {
  const key = siteId ? String(siteId) : "ALL";
  if (deviceCache.has(key)) {
    return deviceCache.get(key);
  }
  if (!siteId || siteId === "ALL") {
    deviceCache.set(key, []);
    return [];
  }
  try {
    const response = await getDevices(siteId);
    const rows = Array.isArray(response?.data)
      ? response.data
      : Array.isArray(response)
      ? response
      : [];
    const devices = rows.map((row) => ({
      device_id: String(row.device_id ?? row.id),
      device_name: row.device_name || `Dispositivo ${row.device_id ?? row.id}`,
    }));
    deviceCache.set(key, devices);
    return devices;
  } catch (err) {
    console.warn("panels.js: unable to load devices for site", siteId, err);
    deviceCache.set(key, []);
    return [];
  }
}

function appendDeviceSelector(container, widget, onChange) {
  const siteId = resolveSiteId(widget, { allowAll: false });
  if (!siteId) return null;
  const controls = document.createElement("div");
  controls.className = "widget-card__controls";
  const label = document.createElement("label");
  label.textContent = "Dispositivo";
  const select = document.createElement("select");
  select.disabled = true;
  const loadingOption = document.createElement("option");
  loadingOption.value = "";
  loadingOption.textContent = "Cargando dispositivos…";
  select.appendChild(loadingOption);
  controls.appendChild(label);
  controls.appendChild(select);
  container.appendChild(controls);

  loadDevicesForSite(siteId)
    .then((devices) => {
      select.innerHTML = "";
      const allOption = document.createElement("option");
      allOption.value = "ALL";
      allOption.textContent = "Todos los dispositivos";
      select.appendChild(allOption);
      if (devices.length === 0) {
        const emptyOption = document.createElement("option");
        emptyOption.value = "";
        emptyOption.textContent = "Sin dispositivos";
        emptyOption.disabled = true;
        select.appendChild(emptyOption);
      } else {
        devices.forEach((device) => {
          const option = document.createElement("option");
          option.value = device.device_id;
          option.textContent = device.device_name;
          select.appendChild(option);
        });
      }
      const selected = String(widget.data_filters?.deviceId || "ALL") || "ALL";
      if (
        Array.from(select.options).some(
          (option) => option.value === selected && !option.disabled
        )
      ) {
        select.value = selected;
      } else {
        select.value = "ALL";
        updateWidgetFilters(widget, { deviceId: "ALL" }).catch(() => {});
      }
    })
    .catch((err) => {
      console.warn("panels.js: unable to populate device selector", err);
      select.innerHTML = "";
      const allOption = document.createElement("option");
      allOption.value = "ALL";
      allOption.textContent = "Todos los dispositivos";
      select.appendChild(allOption);
      select.value = "ALL";
    })
    .finally(() => {
      select.disabled = false;
    });

  select.addEventListener("change", async (event) => {
    await updateWidgetFilters(widget, { deviceId: event.target.value });
    onChange(event.target.value);
  });

  return controls;
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
    console.warn(
      "panels.js: catalog endpoint unavailable, using defaults",
      err
    );
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
  const filterRadios = catalogRoot?.querySelectorAll(
    'input[name="catalog-kind"]'
  );
  if (!catalogList || !catalogEmpty) return;

  catalogList.innerHTML = "";
  if (!state.catalog.length) {
    catalogEmpty.hidden = false;
    return;
  }

  catalogEmpty.hidden = true;
  const query = (searchInput?.value || "").trim().toLowerCase();
  const kindFilter =
    Array.from(filterRadios || []).find((radio) => radio.checked)?.value ||
    "all";

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
        <span class="widget-catalog__meta">${
          definition.source_dataset || "Directo"
        }</span>
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
  container.classList.add(
    kind === "chart" ? "widget-card__body--chart" : "widget-card__body--kpi"
  );
  container.dataset.widgetKind = kind;
}

function renderWidgetLoading(container, message = "Cargando widget…") {
  if (!container) return;
  container.innerHTML = "";
  container.className = "widget-card__body";
  container.dataset.widgetKind = "loading";
  const loadingNode = document.createElement("div");
  loadingNode.className = "widget-card__loading";
  loadingNode.innerHTML = `
    <span class="widget-card__loading-spinner" aria-hidden="true"></span>
    <span>${message}</span>
  `;
  container.appendChild(loadingNode);
}

function renderWidgetCard(widget, definition) {
  const card = document.createElement("article");
  card.className = "widget-card";
  card.dataset.widgetId = String(widget.id);
  card.dataset.widgetSlug = widget.slug;
  card.dataset.widgetKind = definition.kind;
  card.dataset.widgetLoading = widget.isLoading ? "true" : "false";
  applyWidgetCardKind(card, definition.kind);

  if (isEditingMode) {
    if (!widget.isLoading) {
      card.setAttribute("draggable", "true");
      card.classList.add("widget-card--draggable");
    }
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
  if (widget.isLoading) {
    removeBtn.disabled = true;
  }

  const headerInfo = document.createElement("div");
  headerInfo.className = "widget-card__header-info";
  headerInfo.appendChild(title);
  headerInfo.appendChild(meta);

  const headerActions = document.createElement("div");
  headerActions.className = "widget-card__header-actions";
  const infoBadge = createWidgetInfoBadge(widget, definition);
  if (infoBadge) {
    headerActions.appendChild(infoBadge);
  }
  headerActions.appendChild(removeBtn);

  header.appendChild(headerInfo);
  header.appendChild(headerActions);

  const body = document.createElement("div");
  body.className = "widget-card__body";

  card.appendChild(header);
  card.appendChild(body);

  if (widget.isLoading) {
    renderWidgetLoading(body, "Agregando widget…");
    return card;
  }

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
  const wasLoading = card.dataset.widgetLoading === "true";

  card.dataset.widgetId = String(widget.id);
  card.dataset.widgetSlug = widget.slug;
  card.dataset.widgetKind = kind;
  card.dataset.widgetLoading = widget.isLoading ? "true" : "false";
  applyWidgetCardKind(card, kind);
  if (isEditingMode && !widget.isLoading) {
    card.setAttribute("draggable", "true");
    card.classList.add("widget-card--draggable");
  } else {
    card.removeAttribute("draggable");
    card.classList.remove(
      "widget-card--draggable",
      "is-dragging",
      "widget-card--drop-target"
    );
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

  const removeButton = card.querySelector("[data-remove]");
  if (removeButton) {
    removeButton.disabled = !!widget.isLoading;
  }

  syncInfoBadge(card, widget, definition);

  if (!body) return;

  if (widget.isLoading) {
    renderWidgetLoading(body, "Agregando widget…");
    return;
  }

  const shouldRebuild =
    previousKind !== kind || previousSlug !== widget.slug || wasLoading;
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
  const draggedCard = grid.querySelector(
    `.widget-card[data-widget-id="${draggedId}"]`
  );
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
  const withinVertical =
    event.clientY >= rect.top && event.clientY <= rect.bottom;
  let before;
  if (withinVertical) {
    const halfwayX = rect.left + rect.width / 2;
    before = event.clientX < halfwayX;
  } else {
    const halfwayY = rect.top + rect.height / 2;
    before = event.clientY < halfwayY;
  }

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
  grid
    .querySelectorAll(".widget-card--drop-target")
    .forEach((el) => el.classList.remove("widget-card--drop-target"));
  const draggedCard = grid.querySelector(
    `.widget-card[data-widget-id="${activeDragId}"]`
  );
  draggedCard?.classList.remove("is-dragging");
  syncDashboardOrderFromDom();
  activeDragId = null;
}

function handleDragEnd(event) {
  if (!isEditingMode) return;
  const card = event.target.closest(".widget-card");
  card?.classList.remove("is-dragging");
  const grid = document.querySelector("#panel-dashboard .widget-grid");
  grid
    ?.querySelectorAll(".widget-card--drop-target")
    .forEach((el) => el.classList.remove("widget-card--drop-target"));
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
  if (widget.slug === "energy_today_kpi") {
    renderSiteEnergyWidget(widget, container);
    return;
  }
  if (widget.slug === "peak_power_kpi") {
    renderPeakPowerWidget(widget, container);
    return;
  }
  if (widget.slug === "load_factor_kpi") {
    renderLoadFactorWidget(widget, container);
    return;
  }
  if (widget.slug === "pf_compliance_kpi") {
    renderPfComplianceWidget(widget, container);
    return;
  }
  if (widget.slug === "data_freshness_kpi") {
    renderDataFreshnessWidget(widget, container);
    return;
  }
  if (widget.slug === "active_devices_kpi") {
    renderActiveDevicesWidget(widget, container);
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
      if (
        String(widget.data_filters?.siteId ?? state.defaultSiteId) ===
        site.site_id
      ) {
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
      site_id: [String(siteId)],
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
      if (
        String(widget.data_filters?.siteId ?? state.defaultSiteId) ===
        site.site_id
      ) {
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
      kpi_date: [today],
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
    requestBody.filter_map.site_id = [String(siteId)];
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
      const pct =
        record?.availability_pct_avg ?? record?.availability_pct ?? null;
      if (pct === null || pct === undefined) {
        metric.firstChild.textContent = "-";
        helper.textContent = "Sin datos de disponibilidad";
        return;
      }
      const normalized = pct > 1 ? pct : pct * 100;
      metric.firstChild.textContent = `${Number(normalized).toFixed(1)}%`;
      metric.setAttribute(
        "aria-label",
        `Disponibilidad ${metric.firstChild.textContent}`
      );
    })
    .catch((err) => {
      console.error("panels.js: availability fetch failed", err);
      helper.textContent = "No fue posible obtener la disponibilidad";
    });
}

function renderSiteEnergyWidget(widget, container) {
  resetWidgetBody(container, "kpi");
  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__kpi-wrapper";
  container.appendChild(wrapper);
  appendSiteSelector(wrapper, widget, () =>
    renderSiteEnergyWidget(widget, container)
  );

  const metric = document.createElement("div");
  metric.className = "widget-card__metric";
  metric.textContent = "-";
  const helper = document.createElement("small");
  helper.textContent = "Energía generada hoy";
  metric.appendChild(helper);
  wrapper.appendChild(metric);

  const filters = widget.data_filters || {};
  const siteId = filters.siteId || state.defaultSiteId || currentUserSiteId();
  const today = formatDateISO(new Date());

  const requestBody = {
    table: "site_daily_kpi",
    filter_map: {
      kpi_date: [today],
    },
    select_columns: [
      "site_id",
      "total_energy_wh",
      "peak_power_w",
      "load_factor",
      "avg_power_factor",
      "pf_compliance_pct",
      "data_freshness_minutes",
    ],
  };
  if (siteId && siteId !== "ALL") {
    requestBody.filter_map.site_id = [String(siteId)];
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
      const energy = record?.total_energy_wh ?? record?.energy_wh_sum ?? null;
      if (energy === null || energy === undefined) {
        metric.firstChild.textContent = "-";
        helper.textContent = "Energía no disponible";
        return;
      }
      metric.firstChild.textContent = formatEnergy(energy);
      metric.setAttribute(
        "aria-label",
        `Energía total ${metric.firstChild.textContent}`
      );
    })
    .catch((err) => {
      console.error("panels.js: energy kpi failed", err);
      helper.textContent = "Energía no disponible";
    });
}

function renderPeakPowerWidget(widget, container) {
  resetWidgetBody(container, "kpi");
  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__kpi-wrapper";
  container.appendChild(wrapper);
  appendSiteSelector(wrapper, widget, () =>
    renderPeakPowerWidget(widget, container)
  );

  const metric = document.createElement("div");
  metric.className = "widget-card__metric";
  metric.textContent = "-";
  const helper = document.createElement("small");
  helper.textContent = "Potencia pico hoy";
  metric.appendChild(helper);
  wrapper.appendChild(metric);

  const filters = widget.data_filters || {};
  const siteId = filters.siteId || state.defaultSiteId || currentUserSiteId();
  const today = formatDateISO(new Date());

  const requestBody = {
    table: "site_daily_kpi",
    filter_map: {
      kpi_date: [today],
    },
    select_columns: ["site_id", "peak_power_w"],
  };
  if (siteId && siteId !== "ALL") {
    requestBody.filter_map.site_id = [String(siteId)];
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
      const peakPower = record?.peak_power_w ?? null;
      if (peakPower === null || peakPower === undefined) {
        metric.firstChild.textContent = "-";
        helper.textContent = "Potencia pico no disponible";
        return;
      }
      metric.firstChild.textContent = `${Number(peakPower).toFixed(0)} W`;
      metric.setAttribute(
        "aria-label",
        `Potencia pico ${metric.firstChild.textContent}`
      );
    })
    .catch((err) => {
      console.error("panels.js: peak power kpi failed", err);
      helper.textContent = "Potencia pico no disponible";
    });
}

function renderLoadFactorWidget(widget, container) {
  resetWidgetBody(container, "kpi");
  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__kpi-wrapper";
  container.appendChild(wrapper);
  appendSiteSelector(wrapper, widget, () =>
    renderLoadFactorWidget(widget, container)
  );

  const metric = document.createElement("div");
  metric.className = "widget-card__metric";
  metric.textContent = "-";
  const helper = document.createElement("small");
  helper.textContent = "Load factor diario";
  metric.appendChild(helper);
  wrapper.appendChild(metric);

  const filters = widget.data_filters || {};
  const siteId = filters.siteId || state.defaultSiteId || currentUserSiteId();
  const today = formatDateISO(new Date());

  const requestBody = {
    table: "site_daily_kpi",
    filter_map: {
      kpi_date: [today],
    },
    select_columns: ["site_id", "load_factor"],
  };
  if (siteId && siteId !== "ALL") {
    requestBody.filter_map.site_id = [String(siteId)];
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
      const loadFactor = record?.load_factor ?? null;
      if (loadFactor === null || loadFactor === undefined) {
        metric.firstChild.textContent = "-";
        helper.textContent = "Load factor no disponible";
        return;
      }
      const value = loadFactor <= 1 ? loadFactor * 100 : loadFactor;
      metric.firstChild.textContent = `${value.toFixed(1)}%`;
      metric.setAttribute(
        "aria-label",
        `Load factor ${metric.firstChild.textContent}`
      );
    })
    .catch((err) => {
      console.error("panels.js: load factor kpi failed", err);
      helper.textContent = "Load factor no disponible";
    });
}

function renderPfComplianceWidget(widget, container) {
  resetWidgetBody(container, "kpi");
  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__kpi-wrapper";
  container.appendChild(wrapper);
  appendSiteSelector(wrapper, widget, () =>
    renderPfComplianceWidget(widget, container)
  );

  const metric = document.createElement("div");
  metric.className = "widget-card__metric";
  metric.textContent = "-";
  const helper = document.createElement("small");
  helper.textContent = "Cumplimiento PF";
  metric.appendChild(helper);
  wrapper.appendChild(metric);

  const filters = widget.data_filters || {};
  const siteId = filters.siteId || state.defaultSiteId || currentUserSiteId();
  const today = formatDateISO(new Date());

  const requestBody = {
    table: "site_daily_kpi",
    filter_map: {
      kpi_date: [today],
    },
    select_columns: ["site_id", "pf_compliance_pct"],
  };
  if (siteId && siteId !== "ALL") {
    requestBody.filter_map.site_id = [String(siteId)];
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
      const compliance = record?.pf_compliance_pct ?? null;
      if (compliance === null || compliance === undefined) {
        metric.firstChild.textContent = "-";
        helper.textContent = "PF no disponible";
        return;
      }
      metric.firstChild.textContent = formatPercent(compliance);
      metric.setAttribute(
        "aria-label",
        `Cumplimiento de factor de potencia ${metric.firstChild.textContent}`
      );
    })
    .catch((err) => {
      console.error("panels.js: pf compliance kpi failed", err);
      helper.textContent = "PF no disponible";
    });
}

function renderDataFreshnessWidget(widget, container) {
  resetWidgetBody(container, "kpi");
  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__kpi-wrapper";
  container.appendChild(wrapper);
  appendSiteSelector(wrapper, widget, () =>
    renderDataFreshnessWidget(widget, container)
  );

  const metric = document.createElement("div");
  metric.className = "widget-card__metric";
  metric.textContent = "-";
  const helper = document.createElement("small");
  helper.textContent = "Latencia de datos";
  metric.appendChild(helper);
  wrapper.appendChild(metric);

  const filters = widget.data_filters || {};
  const siteId = filters.siteId || state.defaultSiteId || currentUserSiteId();
  const today = formatDateISO(new Date());

  const requestBody = {
    table: "site_daily_kpi",
    filter_map: {
      kpi_date: [today],
    },
    select_columns: ["site_id", "data_freshness_minutes"],
  };
  if (siteId && siteId !== "ALL") {
    requestBody.filter_map.site_id = [String(siteId)];
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
      const freshness = record?.data_freshness_minutes ?? null;
      if (freshness === null || freshness === undefined) {
        metric.firstChild.textContent = "-";
        helper.textContent = "Latencia no disponible";
        return;
      }
      metric.firstChild.textContent = formatMinutes(freshness);
      metric.setAttribute(
        "aria-label",
        `Latencia de datos ${metric.firstChild.textContent}`
      );
    })
    .catch((err) => {
      console.error("panels.js: data freshness kpi failed", err);
      helper.textContent = "Latencia no disponible";
    });
}

function renderActiveDevicesWidget(widget, container) {
  resetWidgetBody(container, "kpi");
  const wrapper = document.createElement("div");
  wrapper.className = "widget-card__kpi-wrapper";
  container.appendChild(wrapper);
  appendSiteSelector(wrapper, widget, () =>
    renderActiveDevicesWidget(widget, container)
  );

  const metric = document.createElement("div");
  metric.className = "widget-card__metric";
  metric.textContent = "-";
  const helper = document.createElement("small");
  helper.textContent = "Dispositivos activos último hora";
  metric.appendChild(helper);
  wrapper.appendChild(metric);

  const filters = widget.data_filters || {};
  const siteId = filters.siteId || state.defaultSiteId || currentUserSiteId();
  const range = computePastHours(24);

  const requestBody = {
    table: "site_hourly_kpi",
    filter_map: {
      hour_start: `[${range.from}, ${range.to}]`,
    },
    select_columns: [
      "site_id",
      "hour_start",
      "active_devices",
      "total_devices",
    ],
  };
  if (siteId && siteId !== "ALL") {
    requestBody.filter_map.site_id = [String(siteId)];
  }

  fetchDB(requestBody)
    .then((response) => {
      const rows = Array.isArray(response?.data)
        ? response.data
        : Array.isArray(response)
        ? response
        : [];
      if (!rows.length) {
        helper.textContent = "Sin actividad reciente";
        return;
      }
      const sorted = rows
        .slice()
        .sort((a, b) => new Date(b.hour_start) - new Date(a.hour_start));
      const record = sorted[0];
      const active = record?.active_devices ?? null;
      const total = record?.total_devices ?? null;
      if (active === null || total === null || total === 0) {
        metric.firstChild.textContent = "-";
        helper.textContent = "Datos incompletos";
        return;
      }
      const pct = (active / total) * 100;
      metric.firstChild.textContent = `${active}/${total}`;
      helper.textContent = `Activos (${pct.toFixed(0)}%)`;
      metric.setAttribute(
        "aria-label",
        `Dispositivos activos ${active} de ${total}`
      );
    })
    .catch((err) => {
      console.error("panels.js: active devices kpi failed", err);
      helper.textContent = "No se pudo calcular";
    });
}

function renderChartWidget(widget, definition, container) {
  resetWidgetBody(container, "chart");

  const siteId = resolveSiteId(widget, {
    allowAll: !DEVICE_FILTER_WIDGETS.has(widget.slug),
  });
  const filters = {
    siteId,
    deviceId: widget.data_filters?.deviceId || "ALL",
    dateRange:
      widget.data_filters?.dateRange || defaultRangeForSlug(widget.slug),
  };
  if (widget.data_filters?.horizon) {
    filters.horizon = Number(widget.data_filters.horizon);
  }
  if (widget.data_filters?.checkLast) {
    filters.checkLast = Number(widget.data_filters.checkLast);
  }

  const requestConfig = buildChartRequest(widget.slug, filters);
  if (!requestConfig) {
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
      await updateWidgetFilters(widget, {
        siteId: event.target.value,
        deviceId: "ALL",
      });
      renderChartWidget(widget, definition, container);
    });
    controls.appendChild(label);
    controls.appendChild(select);
    wrapper.appendChild(controls);
  }

  if (DEVICE_FILTER_WIDGETS.has(widget.slug)) {
    appendDeviceSelector(wrapper, widget, () =>
      renderChartWidget(widget, definition, container)
    );
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

  fetchChart(requestConfig)
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
      return Plotly.react(
        chartNode,
        cleanedFigure.data,
        cleanedFigure.layout,
        plotConfig
      );
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
    filter_map.site_id = [String(siteId)];
  }
  if (deviceId && deviceId !== "ALL") {
    filter_map.device_id = [String(deviceId)];
  }

  switch (slug) {
    case "forecast_power_chart": {
      const range = computeDateRange(1000);
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from} 00:00:00, ${range.to} 23:59:59]`;
      const horizon = Number(filters.horizon) > 0 ? Number(filters.horizon) : 7;
      return {
        endpoint: "/proxy/ml/forecast",
        payload: {
          table: "measurements",
          time_column: "measurement_time",
          target_column: "power_w",
          horizon,
          frequency: "D",
          up_sampling_agg_func: "sum",
          include_conf_int: true,
          filter_map: map,
        },
      };
    }
    case "anomaly_detection_chart": {
      const hours =
        Number(filters.checkLast) > 0 ? Number(filters.checkLast) : 24;
      const lookback = Math.max(hours * 365, 1000);
      const range = computePastHours(lookback);
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        endpoint: "/proxy/ml/anomaly-detection",
        payload: {
          table: "measurements",
          filter_map: map,
          metric_column: "power_w",
          time_column: "measurement_time",
          frequency: "H",
          up_sampling_agg_func: "sum",
          check_last: hours,
        },
      };
    }
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
          title: "Histograma de corriente · semana",
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
          title: "Voltaje vs corriente · mes",
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
          x: "device_name",
          y: "energy_wh_sum",
          style: { orientation: "h", color: "device_id" },
          title: "Energía por dispositivo · semana",
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
          title: "Mapa de calor · semana",
        },
      };
    case "histogram_today_chart": {
      const range = computeTodayRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
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
            nbins: 24,
            color: "device_id",
          },
        },
      };
    }
    case "histogram_month_chart": {
      const range = computeMonthRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              current_a: ["avg"],
            },
            time_window: "D",
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
    }
    case "scatter_today_chart": {
      const range = computeTodayRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
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
    }
    case "scatter_month_chart": {
      const range = computeMonthRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              current_a: ["avg"],
              voltage_v: ["avg"],
            },
            time_window: "D",
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
    }
    case "timeseries_today_chart": {
      const range = computeTodayRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
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
    }
    case "timeseries_month_chart": {
      const range = computeMonthRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              power_w: ["avg"],
            },
            time_window: "D",
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
    }
    case "bar_today_chart": {
      const range = computeTodayRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              energy_wh: ["sum"],
            },
            time_window: "H",
            time_column: "measurement_time",
          },
        ],
        chart: {
          chart_type: "bar",
          x: "device_name",
          y: "energy_wh_sum",
          style: { orientation: "h", color: "device_id" },
        },
      };
    }
    case "bar_month_chart": {
      const range = computeMonthRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
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
          x: "device_name",
          y: "energy_wh_sum",
          style: { orientation: "h", color: "device_id" },
        },
      };
    }
    case "heatmap_today_chart": {
      const range = computeTodayRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      if (deviceId && deviceId !== "ALL") {
        map.device_id = [String(deviceId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
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
    }
    case "heatmap_month_chart": {
      const range = computeMonthRange();
      const map = {};
      if (siteId && siteId !== "ALL") {
        map.site_id = [String(siteId)];
      }
      map.measurement_time = `[${range.from}, ${range.to}]`;
      return {
        table: "measurements",
        filter_map: map,
        aggregation: [
          {
            aggregations: {
              power_w: ["avg"],
            },
            time_window: "D",
            time_column: "measurement_time",
          },
        ],
        chart: {
          chart_type: "heatmap",
          x: "measurement_time",
          y: "site_id",
          z: "power_w_avg",
        },
      };
    }
    case "energy_last7_chart": {
      const range = filters.dateRange || computeDateRange(7);
      const map = {
        kpi_date: `[${range.from}, ${range.to}]`,
      };
      if (filter_map.site_id) {
        map.site_id = filter_map.site_id;
      }
      return {
        table: "site_daily_kpi",
        filter_map: map,
        aggregation: [
          {
            group_by: ["site_id", "kpi_date"],
            aggregations: {
              total_energy_wh: ["sum"],
            },
          },
        ],
        chart: {
          chart_type: "line",
          x: "kpi_date",
          y: "total_energy_wh_sum",
          style: { color: "site_id", shape: "spline" },
        },
      };
    }
    case "power_factor_trend_chart": {
      const range = filters.dateRange || computeDateRange(7);
      const map = {
        kpi_date: `[${range.from}, ${range.to}]`,
      };
      if (filter_map.site_id) {
        map.site_id = filter_map.site_id;
      }
      return {
        table: "site_daily_kpi",
        filter_map: map,
        aggregation: [
          {
            group_by: ["site_id", "kpi_date"],
            aggregations: {
              avg_power_factor: ["avg"],
            },
          },
        ],
        chart: {
          chart_type: "line",
          x: "kpi_date",
          y: "avg_power_factor_avg",
          style: { color: "site_id", shape: "spline" },
        },
      };
    }
    case "availability_trend_chart": {
      const range = filters.dateRange || computeDateRange(3);
      const map = {
        hour_start: `[${range.from} 00:00:00, ${range.to} 23:59:59]`,
      };
      if (filter_map.site_id) {
        map.site_id = filter_map.site_id;
      }
      return {
        table: "site_hourly_kpi",
        filter_map: map,
        aggregation: [
          {
            group_by: ["site_id"],
            aggregations: {
              availability_pct: ["avg"],
            },
            time_window: "H",
            time_column: "hour_start",
          },
        ],
        chart: {
          chart_type: "line",
          x: "hour_start",
          y: "availability_pct_avg",
          style: { color: "site_id" },
        },
      };
    }
    case "device_energy_rank_chart": {
      const range = filters.dateRange || computeDateRange(7);
      const map = {
        kpi_date: `[${range.from}, ${range.to}]`,
      };
      if (filter_map.site_id) {
        map.site_id = filter_map.site_id;
      }
      return {
        table: "device_daily_kpi",
        filter_map: map,
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: {
              energy_wh_sum: ["sum"],
            },
          },
        ],
        chart: {
          chart_type: "bar",
          x: "device_id",
          y: "energy_wh_sum_sum",
          style: { color: "site_id" },
        },
      };
    }
    case "ingestion_lag_chart": {
      const range = filters.dateRange || computeDateRange(14);
      return {
        table: "ingestion_run_kpi",
        filter_map: {
          run_date: `[${range.from}, ${range.to}]`,
        },
        aggregation: [
          {
            group_by: ["run_date"],
            aggregations: {
              ingestion_lag_minutes: ["avg"],
              records_loaded: ["sum"],
            },
          },
        ],
        chart: {
          chart_type: "line",
          x: "run_date",
          y: "ingestion_lag_minutes_avg",
          style: { shape: "spline" },
        },
      };
    }
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

  const tempId = `temp-${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const placeholder = {
    ...createWidgetInstance(definition),
    id: tempId,
    isLoading: true,
  };

  state.dashboard.push(placeholder);
  renderDashboard();
  setDirty(true);

  let resolvedWidget = null;

  if (widgetsApiEnabled) {
    try {
      const response = await fetchJSON(API_ROUTES.attach, {
        method: "POST",
        body: JSON.stringify({ slug }),
      });
      if (response?.widget) {
        const normalised = normaliseWidgetPayload(response.widget);
        if (normalised) {
          resolvedWidget = normalised;
        }
      }
    } catch (err) {
      console.warn("panels.js: attach endpoint unavailable", err);
      if (!err || [404, 501, 503].includes(err.status)) {
        widgetsApiEnabled = false;
      }
    }
  }

  const index = state.dashboard.findIndex((item) => item.id === tempId);
  if (index !== -1) {
    const base = resolvedWidget || placeholder;
    state.dashboard[index] = {
      ...base,
      id: base.id ?? tempId,
      slug: base.slug ?? placeholder.slug,
      kind: base.kind ?? placeholder.kind,
      title: base.title ?? placeholder.title,
      data_filters: base.data_filters ?? placeholder.data_filters,
      visual_config: base.visual_config ?? placeholder.visual_config,
      isLoading: false,
    };
  } else if (resolvedWidget) {
    state.dashboard.push({ ...resolvedWidget, isLoading: false });
  }

  renderDashboard();
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
  const catalogFilters = catalogRoot?.querySelectorAll(
    'input[name="catalog-kind"]'
  );
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
function computeTodayRange() {
  const today = formatDateISO(new Date());
  return {
    from: `${today} 00:00:00`,
    to: `${today} 23:59:59`,
  };
}

function computeMonthRange() {
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  return {
    from: `${formatDateISO(start)} 00:00:00`,
    to: `${formatDateISO(end)} 23:59:59`,
  };
}
