import Plotly from "plotly.js-dist-min";
import {
  ensureAuthenticatedOrRedirect,
  canViewAllSites,
  currentUserSiteId,
} from "../utils/auth";
import { getSites, fetchDB } from "../utils/core";
import {
  fetchPlot,
  applyMapping,
  normalisePlotError,
  plotIsEmpty,
} from "../utils/plot";
import { fillSelect } from "../utils/list";

const AREA_CONFIG = {
  finanzas: {
    cards: [
      {
        key: "energyByDay",
        title: "Consumo de energía por día",
        desc: "kWh consumidos por día dentro del rango de fechas seleccionado.",
        chartType: "line",
      },
      {
        key: "energyBySensor",
        title: "Consumo por sensor",
        desc: "Consumo energético total por sensor para identificar equipos más demandantes.",
        chartType: "bar",
      },
      {
        key: "energyByTariff",
        title: "Consumo por zona horaria",
        desc: "Distribución del consumo en Base / Intermedia / Punta según tarifa aplicada.",
        chartType: "bar",
      },
      {
        key: "costBySensor",
        title: "Costo por sensor",
        desc: "Costo total de energía por sensor considerando tarifas vigentes.",
        chartType: "bar",
      },
      {
        key: "costByTariff",
        title: "Costo por zona horaria",
        desc: "Costo de energía en cada franja horaria para identificar horarios más caros.",
        chartType: "pie",
      },
      {
        key: "financialSummary",
        title: "Resumen financiero",
        desc: "Consumo total, costo total, sensor más costoso, zona horaria más cara y día con mayor consumo.",
        type: "summary",
      },
    ],
  },
  direccion: {
    cards: [
      {
        key: "efficiencyTrend",
        title: "Eficiencia operativa",
        desc: "Evolución del load factor promedio por sitio en el periodo.",
        chartType: "line",
      },
      {
        key: "areaUse",
        title: "Uso por área",
        desc: "Consumo total por sitio para ubicar dónde se concentra la demanda.",
        chartType: "bar",
      },
      {
        key: "loadDistribution",
        title: "Distribución de carga por horario",
        desc: "Carga energética por hora para identificar ventanas costosas.",
        chartType: "bar",
      },
      {
        key: "opex",
        title: "Cumplimiento PF",
        desc: "Cumplimiento del factor de potencia promedio por sitio.",
        chartType: "bar",
      },
      {
        key: "opexMix",
        title: "Mezcla de consumo",
        desc: "Proporción del consumo entre sitios seleccionados.",
        chartType: "pie",
      },
      {
        key: "execSummary",
        title: "Resumen ejecutivo",
        desc: "KPIs de eficiencia, carga y foco de consumo del periodo.",
        type: "summary",
      },
    ],
  },
  mantenimiento: {
    cards: [
      {
        key: "healthTrend",
        title: "Tendencia de consumo diario",
        desc: "kWh registrados por día y por sensor para anticipar sobrecargas.",
        chartType: "line",
      },
      {
        key: "sensorHealth",
        title: "Consumo por sensor",
        desc: "Ranking de sensores por consumo acumulado en el periodo.",
        chartType: "bar",
      },
      {
        key: "downtimeWindows",
        title: "Ventanas críticas",
        desc: "Distribución horaria de la energía para identificar picos.",
        chartType: "bar",
      },
      {
        key: "maintenanceCost",
        title: "Carga promedio por sensor",
        desc: "Promedio de potencia por sensor durante el periodo.",
        chartType: "bar",
      },
      {
        key: "costShare",
        title: "Participación por sensor",
        desc: "Participación relativa del consumo por dispositivo.",
        chartType: "pie",
      },
      {
        key: "maintenanceSummary",
        title: "Resumen de mantenimiento",
        desc: "Sensores destacados y focos de revisión.",
        type: "summary",
      },
    ],
  },
};

const state = {
  activeArea: "finanzas",
  sitesById: {},
  renderToken: 0,
};

const NARRATIVE_AREAS = new Set(["direccion", "mantenimiento"]);

document.addEventListener("DOMContentLoaded", () => {
  ensureAuthenticatedOrRedirect();
  const page = document.getElementById("report-page");
  if (!page) return;

  const elements = {
    form: document.getElementById("report-filters"),
    from: document.getElementById("report-from"),
    to: document.getElementById("report-to"),
    quickRange: document.getElementById("report-quick-range"),
    site: document.getElementById("report-site"),
    areaSelect: document.getElementById("report-area"),
    tabs: Array.from(document.querySelectorAll("[data-area-tab]")),
    grid: document.getElementById("report-grid"),
    download: document.getElementById("download-report"),
    shell: document.querySelector("[data-report-content]"),
  };

  primeDefaultDates(elements.from, elements.to);
  hydrateSites(elements.site);
  if (elements.areaSelect) {
    elements.areaSelect.value = state.activeArea;
  }
  renderArea(state.activeArea, elements);

  elements.form?.addEventListener("submit", (event) => {
    event.preventDefault();
    if (!validateRange(elements.from, elements.to)) return;
    handleRender(elements, state.activeArea);
  });

  elements.quickRange?.addEventListener("change", (event) => {
    applyQuickRange(event.target.value, elements.from, elements.to);
  });

  elements.site?.addEventListener("change", () => {
    handleRender(elements, state.activeArea);
  });

  elements.areaSelect?.addEventListener("change", (event) => {
    const nextArea = event.target.value;
    setActiveTab(nextArea, elements);
    handleRender(elements, nextArea);
  });

  elements.tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const nextArea = tab.dataset.areaTab;
      if (!nextArea) return;
      setActiveTab(nextArea, elements);
      if (elements.areaSelect) {
        elements.areaSelect.value = nextArea;
      }
      handleRender(elements, nextArea);
    });
  });

  elements.download?.addEventListener("click", () => {
    downloadPdf(elements.shell, state.activeArea, elements.from, elements.to);
  });
});

function handleRender(elements, areaKey) {
  state.activeArea = areaKey || state.activeArea;
  state.renderToken += 1;
  renderArea(state.activeArea, elements);
}

function renderArea(areaKey, elements) {
  const renderId = state.renderToken;
  const config = AREA_CONFIG[areaKey] || AREA_CONFIG.finanzas;
  if (!elements.grid) return;
  elements.grid.innerHTML = "";
  const filters = currentFilters(elements, areaKey);
  const narrativeTarget = NARRATIVE_AREAS.has(areaKey)
    ? createNarrativeCard(elements.grid, areaKey)
    : null;
  if (narrativeTarget) {
    filters.narrativeTarget = narrativeTarget;
  }

  config.cards.forEach((card) => {
    const cardEl = document.createElement("article");
    cardEl.className = "report-card";
    if (areaKey !== "finanzas" && card.type !== "summary") {
      cardEl.classList.add("report-card--wide");
    }
    cardEl.innerHTML = `
      <div class="report-card__meta">
        <h3 class="report-card__title">${card.title}</h3>
      </div>
      <p class="report-card__desc">${card.desc}</p>
      ${
        card.type === "summary"
          ? `<div class="summary-panel" data-summary-key="${card.key}"></div>`
          : `<div class="report-chart" data-chart-key="${card.key}"></div>`
      }
      <div class="report-card__footer">
        <span class="dot"></span>
        <span>Última actualización: rango de fechas seleccionado</span>
      </div>
    `;
    elements.grid.appendChild(cardEl);

    if (renderId !== state.renderToken) return;

    if (areaKey === "direccion") {
      loadDireccionCard(card, cardEl, filters, renderId);
    } else if (areaKey === "mantenimiento") {
      loadMantenimientoCard(card, cardEl, filters, renderId);
    } else {
      renderSeededCard(card, cardEl, filters, renderId);
    }
  });
}

function setActiveTab(area, elements) {
  elements.tabs.forEach((tab) => {
    const isActive = tab.dataset.areaTab === area;
    tab.classList.toggle("is-active", isActive);
    tab.setAttribute("aria-selected", isActive ? "true" : "false");
  });
}

/* ------------------------------------------------------------------ */
/*  Dirección                                                         */
/* ------------------------------------------------------------------ */
function loadDireccionCard(card, cardEl, filters, renderId) {
  if (card.type === "summary") {
    const container = cardEl.querySelector("[data-summary-key]");
    renderDireccionSummary(container, filters, renderId);
    return;
  }
  const chartEl = cardEl.querySelector("[data-chart-key]");
  switch (card.key) {
    case "efficiencyTrend":
      renderPlotCard(chartEl, () => buildLoadFactorTrendPayload(filters));
      break;
    case "areaUse":
      renderPlotCard(chartEl, () => buildEnergyBySitePayload(filters, "bar"));
      break;
    case "loadDistribution":
      renderPlotCard(chartEl, () => buildHourlyEnergyPayload(filters));
      break;
    case "opex":
      renderPlotCard(chartEl, () => buildPfCompliancePayload(filters));
      break;
    case "opexMix":
      renderPlotCard(chartEl, () => buildEnergyBySitePayload(filters, "pie"));
      break;
    default:
      setMessage(chartEl, "Sin datos disponibles.");
  }
}

async function renderDireccionSummary(container, filters, renderId) {
  if (!container) return;
  setLoading(container);
  try {
    const [current, previous] = await Promise.all([
      fetchSiteAggregates(filters),
      fetchSiteAggregates(previousRange(filters)),
    ]);
    if (renderId !== state.renderToken) return;
    const energyTotal = sumField(current, "total_energy_wh_sum");
    const availability = avgField(current, "availability_pct_avg");
    const pf = avgField(current, "pf_compliance_pct_avg");
    const topSite = findMaxLabel(current, "total_energy_wh_sum");
    const prevEnergy = sumField(previous, "total_energy_wh_sum");
    const change = prevEnergy > 0 ? ((energyTotal - prevEnergy) / prevEnergy) * 100 : null;

    const rows = [
      { label: "Consumo total", value: formatEnergy(energyTotal) },
      { label: "Disponibilidad promedio", value: formatPercent(availability) },
      { label: "Cumplimiento PF", value: formatPercent(pf) },
      { label: "Sitio más demandante", value: topSite || "—" },
    ];

    renderSummaryRows(container, rows);
    const note = document.createElement("p");
    note.className = "report-note";
    const variation = change === null ? "sin histórico previo" : `${change >= 0 ? "↑" : "↓"} ${Math.abs(change).toFixed(1)}% vs periodo anterior`;
    note.textContent = `En el periodo ${filters.from} a ${filters.to} se registraron ${formatEnergy(energyTotal)}. ${variation}.`;
    container.appendChild(note);
    renderNarrative("direccion", filters, {
      energyTotal,
      change,
      topSite,
    });
  } catch (err) {
    if (renderId !== state.renderToken) return;
    console.error("reports: dirección summary", err);
    setMessage(container, "No se pudo calcular el resumen.");
    renderNarrative("direccion", filters, null, err);
  }
}

/* ------------------------------------------------------------------ */
/*  Mantenimiento                                                     */
/* ------------------------------------------------------------------ */
function loadMantenimientoCard(card, cardEl, filters, renderId) {
  if (card.type === "summary") {
    const container = cardEl.querySelector("[data-summary-key]");
    renderMantenimientoSummary(container, filters, renderId);
    return;
  }
  const chartEl = cardEl.querySelector("[data-chart-key]");
  switch (card.key) {
    case "healthTrend":
      renderPlotCard(chartEl, () => buildDeviceEnergyTrendPayload(filters));
      break;
    case "sensorHealth":
      renderPlotCard(chartEl, () => buildDeviceEnergyRankPayload(filters, "bar"));
      break;
    case "downtimeWindows":
      renderPlotCard(chartEl, () => buildHourlyEnergyPayload(filters));
      break;
    case "maintenanceCost":
      renderPlotCard(chartEl, () => buildDevicePowerPayload(filters));
      break;
    case "costShare":
      renderPlotCard(chartEl, () => buildDeviceEnergyRankPayload(filters, "pie"));
      break;
    default:
      setMessage(chartEl, "Sin datos disponibles.");
  }
}

async function renderMantenimientoSummary(container, filters, renderId) {
  if (!container) return;
  setLoading(container);
  try {
    const rows = await fetchDeviceAggregates(filters);
    if (renderId !== state.renderToken) return;
    const energyTotal = sumField(rows, "energy_wh_sum_sum");
    const topDevice = findMaxLabel(rows, "energy_wh_sum_sum");
    const ordered = rows
      .slice()
      .sort((a, b) => (b.energy_wh_sum_sum || 0) - (a.energy_wh_sum_sum || 0))
      .slice(0, 3)
      .map((r) => r.device_id)
      .filter(Boolean);

    const summaryRows = [
      { label: "Consumo total", value: formatEnergy(energyTotal) },
      { label: "Dispositivos monitoreados", value: `${rows.length || 0}` },
      { label: "Sensor más demandante", value: topDevice || "—" },
      { label: "Top 3 consumo", value: ordered.length ? ordered.join(", ") : "—" },
    ];
    renderSummaryRows(container, summaryRows);
    const note = document.createElement("p");
    note.className = "report-note";
    note.textContent = ordered.length
      ? `Revisar ${ordered[0]} y ${ordered[1] || ordered[0]}: concentran la mayor carga en el periodo seleccionado.`
      : "Sin dispositivos destacados en el periodo.";
    container.appendChild(note);
    renderNarrative("mantenimiento", filters, {
      energyTotal,
      devices: rows.length,
      topDevices: ordered,
    });
  } catch (err) {
    if (renderId !== state.renderToken) return;
    console.error("reports: mantenimiento summary", err);
    setMessage(container, "No se pudo calcular el resumen.");
    renderNarrative("mantenimiento", filters, null, err);
  }
}

/* ------------------------------------------------------------------ */
/*  Fallback for Finanzas (placeholder seeded charts)                 */
/* ------------------------------------------------------------------ */
function renderSeededCard(card, cardEl, filters, renderId) {
  if (renderId !== state.renderToken) return;
  if (card.type === "summary") {
    const container = cardEl.querySelector("[data-summary-key]");
    const stats = buildSeededStats(filters, card.key);
    renderSummaryRows(container, stats);
    return;
  }
  const chartEl = cardEl.querySelector("[data-chart-key]");
  renderSeededChart(chartEl, card.chartType, card.key, filters);
}

/* ------------------------------------------------------------------ */
/*  Payload builders                                                  */
/* ------------------------------------------------------------------ */
function buildBaseSiteMap(filters, column = "kpi_date") {
  const map = {};
  if (column === "kpi_date") {
    map.kpi_date = `[${filters.from}, ${filters.to}]`;
  } else if (column === "hour_start") {
    map.hour_start = `[${filters.from} 00:00:00, ${filters.to} 23:59:59]`;
  }
  if (filters.siteId && filters.siteId !== "ALL") {
    map.site_id = [String(filters.siteId)];
  }
  return map;
}

function buildLoadFactorTrendPayload(filters) {
  const filter_map = buildBaseSiteMap(filters);
  return {
    table: "site_daily_kpi",
    filter_map,
    aggregation: [
      {
        group_by: ["site_id", "kpi_date"],
        aggregations: {
          load_factor: ["avg"],
        },
      },
    ],
    chart: {
      chart_type: "line",
      x: "kpi_date",
      y: "load_factor_avg",
      style: { color: "site_id", shape: "spline" },
    },
  };
}

function buildEnergyBySitePayload(filters, chartType = "bar") {
  const filter_map = buildBaseSiteMap(filters);
  return {
    table: "site_daily_kpi",
    filter_map,
    aggregation: [
      {
        group_by: ["site_id"],
        aggregations: {
          total_energy_wh: ["sum"],
        },
      },
    ],
    chart: {
      chart_type: chartType,
      x: "site_id",
      y: "total_energy_wh_sum",
      style: { color: "site_id" },
    },
  };
}

function buildHourlyEnergyPayload(filters) {
  const filter_map = buildBaseSiteMap(filters, "hour_start");
  return {
    table: "site_hourly_kpi",
    filter_map,
    aggregation: [
      {
        group_by: ["site_id", "hour_start"],
        aggregations: {
          energy_wh_sum: ["avg"],
        },
        time_window: "H",
        time_column: "hour_start",
      },
    ],
    chart: {
      chart_type: "bar",
      x: "hour_start",
      y: "energy_wh_sum_avg",
      style: { color: "site_id" },
    },
  };
}

function buildPfCompliancePayload(filters) {
  const filter_map = buildBaseSiteMap(filters);
  return {
    table: "site_daily_kpi",
    filter_map,
    aggregation: [
      {
        group_by: ["site_id"],
        aggregations: {
          pf_compliance_pct: ["avg"],
        },
      },
    ],
    chart: {
      chart_type: "bar",
      x: "site_id",
      y: "pf_compliance_pct_avg",
      style: { color: "site_id" },
    },
  };
}

function buildDeviceEnergyTrendPayload(filters) {
  const filter_map = buildBaseSiteMap(filters);
  return {
    table: "device_daily_kpi",
    filter_map,
    aggregation: [
      {
        group_by: ["device_id", "kpi_date"],
        aggregations: {
          energy_wh_sum: ["sum"],
        },
      },
    ],
    chart: {
      chart_type: "line",
      x: "kpi_date",
      y: "energy_wh_sum_sum",
      style: { color: "device_id", shape: "spline" },
    },
  };
}

function buildDeviceEnergyRankPayload(filters, chartType = "bar") {
  const filter_map = buildBaseSiteMap(filters);
  return {
    table: "device_daily_kpi",
    filter_map,
    aggregation: [
      {
        group_by: ["device_id"],
        aggregations: {
          energy_wh_sum: ["sum"],
        },
      },
    ],
    chart: {
      chart_type: chartType,
      x: "device_id",
      y: "energy_wh_sum_sum",
      style: { color: "device_id" },
    },
  };
}

function buildDevicePowerPayload(filters) {
  const filter_map = buildBaseSiteMap(filters);
  return {
    table: "device_daily_kpi",
    filter_map,
    aggregation: [
      {
        group_by: ["device_id"],
        aggregations: {
          power_w_avg: ["avg"],
        },
      },
    ],
    chart: {
      chart_type: "bar",
      x: "device_id",
      y: "power_w_avg_avg",
      style: { color: "device_id" },
    },
  };
}

/* ------------------------------------------------------------------ */
/*  Data fetchers                                                     */
/* ------------------------------------------------------------------ */
async function fetchSiteAggregates(filters) {
  const body = {
    table: "site_daily_kpi",
    filter_map: buildBaseSiteMap(filters),
    aggregation: [
      {
        group_by: ["site_id"],
        aggregations: {
          total_energy_wh: ["sum"],
          availability_pct: ["avg"],
          pf_compliance_pct: ["avg"],
        },
      },
    ],
  };
  const response = await fetchDB(body);
  return Array.isArray(response?.data)
    ? response.data
    : Array.isArray(response)
    ? response
    : [];
}

async function fetchDeviceAggregates(filters) {
  const body = {
    table: "device_daily_kpi",
    filter_map: buildBaseSiteMap(filters),
    aggregation: [
      {
        group_by: ["device_id"],
        aggregations: {
          energy_wh_sum: ["sum"],
          power_w_avg: ["avg"],
        },
      },
    ],
  };
  const response = await fetchDB(body);
  return Array.isArray(response?.data)
    ? response.data
    : Array.isArray(response)
    ? response
    : [];
}

/* ------------------------------------------------------------------ */
/*  Plot helpers                                                      */
/* ------------------------------------------------------------------ */
async function renderPlotCard(container, payloadBuilder) {
  if (!container) return;
  setLoading(container);
  let payload;
  try {
    payload = payloadBuilder();
  } catch (err) {
    console.error("reports: payload error", err);
    setMessage(container, "Error al preparar la consulta.");
    return;
  }

  try {
    const { figure, config, mapping } = await fetchPlot(payload);
    applyMapping(figure, mapping);
    if (plotIsEmpty(figure)) {
      setMessage(container, "No hay datos para los filtros seleccionados.");
      return;
    }
    const normalizedLayout = normalizeReportPlotLayout(figure.layout);
    Plotly.react(container, figure.data, normalizedLayout, {
      ...config,
      displaylogo: false,
      responsive: true,
    });
  } catch (err) {
    console.error("reports: plot error", err);
    const { message } = normalisePlotError(err);
    setError(container, message || "No fue posible cargar la gráfica.", () =>
      renderPlotCard(container, payloadBuilder)
    );
  }
}

/* ------------------------------------------------------------------ */
/*  Seeded fallback utilities (Finanzas)                              */
/* ------------------------------------------------------------------ */
function renderSeededChart(container, chartType, cardKey, filters) {
  if (!container) return;
  const seed = hashSeed(`${cardKey}-${filters.from}-${filters.to}`);
  const dates = buildDateLabels(filters, 7);
  if (chartType === "line") {
    const values = buildSeries(seed, dates.length, 180, 520);
    Plotly.react(
      container,
      [
        {
          type: "scatter",
          mode: "lines+markers",
          x: dates,
          y: values,
          line: { color: "#d46652", width: 2 },
          marker: { color: "#d46652", size: 6 },
          hovertemplate: "%{x}<br>%{y:.0f} kWh<extra></extra>",
        },
      ],
      baseLayout(),
      baseConfig()
    );
  } else if (chartType === "bar") {
    const labels = ["Sensor A", "Sensor B", "Sensor C", "Sensor D"];
    const values = buildSeries(seed + 3, labels.length, 120, 420);
    Plotly.react(
      container,
      [
        {
          type: "bar",
          x: labels,
          y: values,
          marker: { color: "#d46652" },
        },
      ],
      baseLayout(),
      baseConfig()
    );
  } else if (chartType === "pie") {
    const labels = ["Base", "Intermedia", "Punta"];
    const values = buildSeries(seed + 5, labels.length, 10, 60);
    Plotly.react(
      container,
      [
        {
          type: "pie",
          labels,
          values,
          marker: { colors: ["#f6d6c7", "#e79e87", "#d46652"] },
          hole: 0.35,
        },
      ],
      baseLayout({ margin: { t: 10, b: 10 } }),
      baseConfig()
    );
  }
}

function buildSeededStats(filters, cardKey) {
  const seed = hashSeed(`${cardKey}-${filters.from}-${filters.to}`);
  const energy = Math.round(1500 + seededRandom(seed) * 1200);
  const cost = Math.round(5200 + seededRandom(seed + 2) * 3800);
  const sensor = ["Sensor A", "Sensor B", "Sensor C", "Sensor D"][
    Math.floor(seededRandom(seed + 3) * 4)
  ];
  const zone = ["Base", "Intermedia", "Punta"][
    Math.floor(seededRandom(seed + 4) * 3)
  ];
  const topDay = buildDateLabels(filters, 1)[0] || "—";
  return [
    { label: "Consumo total", value: formatEnergy(energy) },
    { label: "Costo total", value: formatCurrency(cost) },
    { label: "Sensor más costoso", value: sensor },
    { label: "Zona más cara", value: zone },
    { label: "Día mayor consumo", value: topDay },
  ];
}

/* ------------------------------------------------------------------ */
/*  UI helpers                                                        */
/* ------------------------------------------------------------------ */
function setLoading(container) {
  if (!container) return;
  container.innerHTML = `<div class="report-note">Cargando...</div>`;
}

function setMessage(container, text) {
  if (!container) return;
  container.innerHTML = `<div class="report-note">${text}</div>`;
}

function setError(container, text, onRetry) {
  if (!container) return;
  const btn = onRetry
    ? `<button type="button" class="report-button report-button--ghost" data-report-retry>Reintentar</button>`
    : "";
  container.innerHTML = `<div class="report-note">${text}</div>${btn}`;
  if (onRetry) {
    container
      .querySelector("[data-report-retry]")
      ?.addEventListener("click", () => onRetry());
  }
}

function renderSummaryRows(container, rows) {
  if (!container || !Array.isArray(rows)) return;
  container.innerHTML = "";
  rows.forEach((row) => {
    const item = document.createElement("div");
    item.className = "summary-row";
    item.innerHTML = `<span>${row.label}</span><strong>${row.value}</strong>`;
    container.appendChild(item);
  });
}

function primeDefaultDates(fromInput, toInput) {
  if (!fromInput || !toInput) return;
  const today = new Date();
  const start = new Date(today);
  start.setDate(start.getDate() - 6);
  fromInput.value = formatDateISO(start);
  toInput.value = formatDateISO(today);
}

function applyQuickRange(value, fromInput, toInput) {
  if (!fromInput || !toInput) return;
  const today = new Date();
  let start = new Date(today);
  switch (value) {
    case "today":
      start = new Date(today);
      break;
    case "last7":
      start.setDate(start.getDate() - 6);
      break;
    case "month":
      start.setDate(1);
      break;
    default:
      return;
  }
  fromInput.value = formatDateISO(start);
  toInput.value = formatDateISO(today);
}

function validateRange(fromInput, toInput) {
  if (!fromInput || !toInput) return true;
  if (fromInput.value && toInput.value && fromInput.value > toInput.value) {
    alert("Rango inválido: la fecha inicial es mayor que la final.");
    return false;
  }
  return true;
}

function formatDateISO(date) {
  return date.toISOString().slice(0, 10);
}

function buildDateLabels(range, maxPoints) {
  const start = new Date(range.from);
  const end = new Date(range.to);
  const diffDays = Math.max(
    1,
    Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24))
  );
  const steps = Math.min(diffDays + 1, maxPoints);
  const labels = [];
  for (let i = 0; i < steps; i++) {
    const date = new Date(start);
    const step = Math.round((diffDays / (steps - 1 || 1)) * i);
    date.setDate(start.getDate() + step);
    labels.push(
      date.toLocaleDateString("es-MX", { day: "2-digit", month: "2-digit" })
    );
  }
  return labels;
}

function buildSeries(seed, length, min, max) {
  const values = [];
  for (let i = 0; i < length; i++) {
    const factor = seededRandom(seed + i);
    values.push(Math.round(min + (max - min) * factor));
  }
  return values;
}

function seededRandom(seed) {
  const x = Math.sin(seed) * 10000;
  return x - Math.floor(x);
}

function hashSeed(text) {
  let hash = 0;
  for (let i = 0; i < text.length; i++) {
    hash = (hash << 5) - hash + text.charCodeAt(i);
    hash |= 0;
  }
  return Math.abs(hash) + 1;
}

function baseLayout(overrides = {}) {
  return {
    margin: { l: 24, r: 12, t: 8, b: 28 },
    paper_bgcolor: "rgba(0,0,0,0)",
    plot_bgcolor: "rgba(0,0,0,0)",
    xaxis: { tickfont: { color: "#737380" }, linecolor: "#f2ebe3" },
    yaxis: { tickfont: { color: "#737380" }, gridcolor: "#f2ebe3" },
    ...overrides,
  };
}

function baseConfig() {
  return {
    displaylogo: false,
    responsive: true,
    modeBarButtonsToRemove: [
      "zoom2d",
      "pan2d",
      "select2d",
      "lasso2d",
      "zoomIn2d",
      "zoomOut2d",
      "autoScale2d",
      "resetScale2d",
      "hoverClosestCartesian",
      "hoverCompareCartesian",
      "toggleSpikelines",
      "toImage",
    ],
    displayModeBar: false,
  };
}

function currentFilters(elements, areaKey) {
  const from = elements.from?.value || formatDateISO(new Date());
  const to = elements.to?.value || formatDateISO(new Date());
  const siteId = resolveSite(elements.site);
  return { from, to, siteId, area: areaKey };
}

function resolveSite(siteSelect) {
  if (canViewAllSites()) {
    return siteSelect?.value || "";
  }
  return currentUserSiteId() || "";
}

async function hydrateSites(select) {
  if (!select) return;
  if (!canViewAllSites()) {
    const siteId = currentUserSiteId();
    if (siteId) {
      select.innerHTML = `<option value="${siteId}" selected>Sitio actual</option>`;
    }
    return;
  }
  try {
    const sites = await getSites();
    const rows = Array.isArray(sites?.data)
      ? sites.data
      : Array.isArray(sites)
      ? sites
      : [];
    fillSelect(select, rows, "site_id", "site_name");
    select.insertAdjacentHTML(
      "afterbegin",
      '<option value="">Todos los sitios</option>'
    );
    select.value = "";
    rows.forEach((row) => {
      state.sitesById[String(row.site_id)] = row.site_name;
    });
  } catch (error) {
    console.warn("reports: no se pudieron cargar sitios", error);
  }
}

function previousRange(range) {
  const fromDate = new Date(range.from);
  const toDate = new Date(range.to);
  const diffDays = Math.max(
    1,
    Math.round((toDate.getTime() - fromDate.getTime()) / (1000 * 60 * 60 * 24))
  );
  const prevTo = new Date(fromDate);
  prevTo.setDate(prevTo.getDate() - 1);
  const prevFrom = new Date(prevTo);
  prevFrom.setDate(prevFrom.getDate() - diffDays);
  return { from: formatDateISO(prevFrom), to: formatDateISO(prevTo) };
}

function sumField(rows, field) {
  if (!Array.isArray(rows)) return 0;
  return rows.reduce((acc, row) => acc + (Number(row?.[field]) || 0), 0);
}

function avgField(rows, field) {
  if (!Array.isArray(rows) || !rows.length) return 0;
  const total = rows.reduce((acc, row) => acc + (Number(row?.[field]) || 0), 0);
  return total / rows.length;
}

function findMaxLabel(rows, field) {
  if (!Array.isArray(rows) || !rows.length) return null;
  const sorted = rows
    .slice()
    .sort((a, b) => (Number(b?.[field]) || 0) - (Number(a?.[field]) || 0));
  const top = sorted[0];
  const siteId = top?.site_id || top?.device_id;
  if (!siteId) return null;
  return state.sitesById[String(siteId)] || siteId;
}

function formatEnergy(value) {
  return `${Number(value ?? 0).toLocaleString("es-MX")} kWh`;
}

function formatCurrency(value) {
  return `$${Number(value ?? 0).toLocaleString("es-MX")} MXN`;
}

function formatPercent(value) {
  if (value === null || value === undefined || Number.isNaN(value)) return "—";
  const normalized = value > 1 ? value : value * 100;
  return `${normalized.toFixed(1)}%`;
}

function normalizeReportPlotLayout(layout = {}) {
  // Strip titles/axes to keep cards clean and let the card header act as title
  const next = { ...layout };
  next.title = "";
  if (next?.title && typeof next.title === "object") {
    next.title.text = "";
  }
  next.xaxis = {
    ...(layout.xaxis || {}),
    title: "",
    titlefont: { size: 11 },
    tickfont: { color: "#737380", size: 10 },
  };
  next.yaxis = {
    ...(layout.yaxis || {}),
    title: "",
    titlefont: { size: 11 },
    tickfont: { color: "#737380", size: 10 },
  };
  next.margin = layout.margin || { l: 30, r: 10, t: 10, b: 30 };
  next.autosize = true;
  return next;
}

function describeRange(range) {
  if (!range?.from || !range?.to) return "el periodo seleccionado";
  return `${range.from} a ${range.to}`;
}

function formatToday() {
  try {
    return new Intl.DateTimeFormat("es-MX", { dateStyle: "long" }).format(
      new Date()
    );
  } catch {
    return formatDateISO(new Date());
  }
}

function siteLabel(siteId) {
  if (!canViewAllSites()) {
    return "el sitio asignado";
  }
  if (!siteId) return "todos los sitios";
  return state.sitesById[String(siteId)] || `Sitio ${siteId}`;
}

function createNarrativeCard(grid, areaKey) {
  const card = document.createElement("article");
  card.className = "report-card report-card--wide";
  card.innerHTML = `
    <div class="report-card__meta">
      <h3 class="report-card__title">${areaKey === "direccion" ? "Resumen ejecutivo" : "Resumen operativo"}</h3>
    </div>
    <div class="report-narrative" data-narrative>Preparando narrativa...</div>
    <div class="report-card__footer">
      <span class="dot"></span>
      <span>Contexto generado con los datos filtrados</span>
    </div>
  `;
  grid.appendChild(card);
  return card.querySelector("[data-narrative]");
}

function renderNarrative(areaKey, filters, data, err) {
  // Build a short text summary tying charts/KPIs together for the current area
  const target = filters?.narrativeTarget;
  if (!target) return;
  if (err) {
    target.textContent =
      "No se pudo generar la narrativa con los datos actuales.";
    return;
  }
  const rangeLabel = describeRange(filters);
  const today = formatToday();
  const siteName = siteLabel(filters.siteId);
  if (areaKey === "direccion") {
    const energy = data?.energyTotal ? formatEnergy(data.energyTotal) : "—";
    const topSite = data?.topSite || "—";
    const change =
      data && typeof data.change === "number"
        ? `${data.change >= 0 ? "↑" : "↓"} ${Math.abs(data.change).toFixed(1)}% vs periodo previo`
        : "sin referencia previa";
    target.textContent = `Al ${today}, ${siteName} registró ${energy} en el periodo ${rangeLabel}; ${change}. El sitio con mayor demanda fue ${topSite}.`;
    return;
  }

  if (areaKey === "mantenimiento") {
    const devices = data?.devices ?? 0;
    const top = Array.isArray(data?.topDevices) ? data.topDevices : [];
    const topText = top.length
      ? `Sensores destacados: ${top.join(", ")}.`
      : "No hay sensores destacados en este rango.";
    target.textContent = `En ${rangeLabel} se monitorearon ${devices} dispositivos en ${siteName}. ${topText}`;
    return;
  }
}

let pdfDepsPromise;
async function ensurePdfDeps() {
  if (pdfDepsPromise) return pdfDepsPromise;
  pdfDepsPromise = new Promise((resolve, reject) => {
    const loadScript = (src) =>
      new Promise((res, rej) => {
        const script = document.createElement("script");
        script.src = src;
        script.async = true;
        script.onload = res;
        script.onerror = () => rej(new Error(`No se pudo cargar ${src}`));
        document.head.appendChild(script);
      });
    loadScript(
      "https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"
    )
      .then(() =>
        loadScript(
          "https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"
        )
      )
      .then(resolve)
      .catch(reject);
  });
  return pdfDepsPromise;
}

async function downloadPdf(shell, area, fromInput, toInput) {
  if (!shell) return;
  const btn = document.getElementById("download-report");
  try {
    if (btn) {
      btn.disabled = true;
      btn.textContent = "Generando PDF...";
    }
    await ensurePdfDeps();
    const canvas = await window.html2canvas(shell, {
      scale: 2,
      useCORS: true,
    });
    const imgData = canvas.toDataURL("image/png");
    const pdf = new window.jspdf.jsPDF("p", "pt", "a4");
    const pdfWidth = pdf.internal.pageSize.getWidth();
    const pdfHeight = pdf.internal.pageSize.getHeight();
    const imgProps = {
      width: canvas.width,
      height: canvas.height,
    };
    const ratio = Math.min(pdfWidth / imgProps.width, 1);
    const imgHeight = imgProps.height * ratio;
    let position = 0;
    let heightLeft = imgHeight;

    while (heightLeft > 0) {
      pdf.addImage(imgData, "PNG", 0, position, pdfWidth, imgHeight);
      heightLeft -= pdfHeight;
      if (heightLeft > 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
      }
    }

    const nameFrom = fromInput?.value || "inicio";
    const nameTo = toInput?.value || "fin";
    pdf.save(`reporte-${area}-${nameFrom}-a-${nameTo}.pdf`);
  } catch (error) {
    console.error("reports: error al generar PDF", error);
    alert("No se pudo generar el PDF. Intenta nuevamente.");
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = "Descargar PDF";
    }
  }
}
