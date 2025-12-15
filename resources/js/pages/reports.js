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
  devicesById: {}, // raw names keyed by device_id
  deviceMetaById: {},
  renderToken: 0,
  sitesReady: false,
  currentSiteId: "",
};

const NARRATIVE_AREAS = new Set(["direccion", "mantenimiento"]);
const AREA_LABELS = {
  finanzas: "Finanzas",
  direccion: "Dirección",
  mantenimiento: "Mantenimiento",
};

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
    tabs: Array.from(document.querySelectorAll("[data-area-tab]")),
    grid: document.getElementById("report-grid"),
    download: document.getElementById("download-report"),
    shell: document.querySelector("[data-report-content]"),
  };

  primeDefaultDates(elements.from, elements.to);
  (async () => {
    await hydrateSites(elements.site);
    state.sitesReady = true;
    await renderArea(state.activeArea, elements);
  })();

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

  elements.tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      const nextArea = tab.dataset.areaTab;
      if (!nextArea) return;
      setActiveTab(nextArea, elements);
      handleRender(elements, nextArea);
    });
  });

  elements.download?.addEventListener("click", () => {
    downloadPdf(elements.shell, state.activeArea, elements.from, elements.to);
  });
});

async function handleRender(elements, areaKey) {
  if (!state.sitesReady) return;
  state.activeArea = areaKey || state.activeArea;
  state.renderToken += 1;
  await renderArea(state.activeArea, elements);
}

async function renderArea(areaKey, elements) {
  const renderId = state.renderToken;
  const config = AREA_CONFIG[areaKey] || AREA_CONFIG.finanzas;
  if (!elements.grid) return;
  elements.grid.innerHTML = "";
  const filters = currentFilters(elements, areaKey);
  // Preload device labels (all sites when needed) to de-ambiguate legends/summaries
  if (areaKey === "mantenimiento") {
    await ensureDeviceLabels(filters.siteId || "ALL");
  }
  await renderComparisonSection(areaKey, elements.grid, filters, renderId);
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
    if (card.type === "summary") {
      cardEl.classList.add("report-card--full");
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

async function renderComparisonSection(areaKey, grid, filters, renderId) {
  if (!grid || areaKey === "finanzas") return;
  const container = document.createElement("article");
  container.className = "report-card report-card--wide";
  container.innerHTML = `
    <div class="report-card__meta">
      <h3 class="report-card__title">Comparativa vs. periodo anterior</h3>
    </div>
    <div class="comparison-grid" data-comparison-grid>
      <p class="report-note">Calculando comparativos...</p>
    </div>
    <div class="report-card__footer">
      <span class="dot"></span>
      <span>Comparativo automático según los filtros actuales</span>
    </div>
  `;
  grid.appendChild(container);
  const target = container.querySelector("[data-comparison-grid]");
  const prevRange = getPreviousPeriod(filters.from, filters.to);
  const prevFilters = { ...filters, ...prevRange };

  try {
    if (areaKey === "mantenimiento") {
      // Ensure we have device labels for both current and previous data
      await ensureDeviceLabels(filters.siteId || "ALL");
    }
    const [current, previous] =
      areaKey === "direccion"
        ? await Promise.all([
            fetchSiteAggregates(filters),
            fetchSiteAggregates(prevFilters),
          ])
        : await Promise.all([
            fetchDeviceAggregates(filters),
            fetchDeviceAggregates(prevFilters),
          ]);
    if (renderId !== state.renderToken) return;
    if (areaKey === "mantenimiento") {
      const allIds = [...current, ...previous].map((r) => r.device_id);
      await ensureDeviceMetaForIds(allIds);
    }
    const cards =
      areaKey === "direccion"
        ? buildDireccionComparisons(current, previous, filters, prevRange)
        : buildMantenimientoComparisons(current, previous, filters, prevRange);
    renderComparisonCards(target, cards);
  } catch (error) {
    if (renderId !== state.renderToken) return;
    console.error("reports: comparison error", error);
    setMessage(
      target,
      "No se pudieron calcular las comparativas. Intenta nuevamente más tarde."
    );
  }
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
    await ensureDeviceLabels(filters.siteId || "ALL");
    const rows = await fetchDeviceAggregates(filters);
    if (renderId !== state.renderToken) return;
    await ensureDeviceMetaForIds(rows.map((r) => r.device_id));
    const enriched = rows.map((row) => ({
      ...row,
      device_name: deviceLabel(row.device_id, row.device_name, row.site_id),
    }));
    const energyTotal = sumField(enriched, "energy_wh_sum_sum");
    const topDevice = findMaxLabel(enriched, "energy_wh_sum_sum");
    const ordered = enriched
      .slice()
      .sort((a, b) => (b.energy_wh_sum_sum || 0) - (a.energy_wh_sum_sum || 0))
      .slice(0, 3)
      .map((r) => deviceLabel(r.device_id, r.device_name, r.site_id))
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
  const pieFallback = chartType === "pie";
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
      // backend does not support "pie"; fallback to bar while keeping intent
      chart_type: pieFallback ? "bar" : chartType,
      x: "site_id",
      y: "total_energy_wh_sum",
      style: pieFallback ? { color: "site_id", orientation: "h" } : { color: "site_id" },
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
        group_by: ["site_id", "device_id", "kpi_date"],
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
  const pieFallback = chartType === "pie";
  return {
    table: "device_daily_kpi",
    filter_map,
    aggregation: [
      {
        group_by: ["site_id", "device_id"],
        aggregations: {
          energy_wh_sum: ["sum"],
        },
      },
    ],
    chart: {
      // backend lacks native "pie"; fallback to bar while keeping grouping
      chart_type: pieFallback ? "bar" : chartType,
      x: "device_id",
      y: "energy_wh_sum_sum",
      style: pieFallback
        ? { color: "device_id", orientation: "h" }
        : { color: "device_id" },
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
        group_by: ["site_id", "device_id"],
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
  try {
    const response = await fetchDB(body);
    return Array.isArray(response?.data)
      ? response.data
      : Array.isArray(response)
      ? response
      : [];
  } catch (error) {
    console.warn("reports: site aggregates unavailable", error);
    return [];
  }
}

async function fetchDeviceAggregates(filters) {
  const body = {
    table: "device_daily_kpi",
    filter_map: buildBaseSiteMap(filters),
    aggregation: [
      {
        group_by: ["site_id", "device_id"],
        aggregations: {
          energy_wh_sum: ["sum"],
          power_w_avg: ["avg"],
        },
      },
    ],
  };
  try {
    const response = await fetchDB(body);
    return Array.isArray(response?.data)
      ? response.data
      : Array.isArray(response)
      ? response
      : [];
  } catch (error) {
    console.warn("reports: device aggregates unavailable", error);
    return [];
  }
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
    const augmentedMapping = augmentDeviceMapping(mapping);
    applyMapping(figure, augmentedMapping);
    mapCategoricalAxisToLabels(figure);
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
    const text =
      typeof message === "string"
        ? message
        : "No fue posible cargar la gráfica.";
    setError(container, text, () =>
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
  state.currentSiteId = siteId;
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
  try {
    const sites = await getSites();
    const rows = Array.isArray(sites?.data)
      ? sites.data
      : Array.isArray(sites)
      ? sites
      : [];
    if (!rows.length) {
      const siteId = currentUserSiteId();
      if (siteId) {
        select.innerHTML = `<option value="${siteId}" selected>Sitio ${siteId}</option>`;
        state.sitesById[String(siteId)] = `Sitio ${siteId}`;
      }
      return;
    }
    fillSelect(select, rows, "site_id", "site_name");
    rows.forEach((row) => {
      state.sitesById[String(row.site_id)] = row.site_name;
    });
    if (canViewAllSites()) {
      select.insertAdjacentHTML(
        "afterbegin",
        '<option value="">Todos los sitios</option>'
      );
      const defaultSite = rows[0]?.site_id;
      if (defaultSite) {
        select.value = String(defaultSite);
      }
    } else {
      const current = currentUserSiteId();
      if (current) select.value = String(current);
    }
  } catch (error) {
    console.warn("reports: no se pudieron cargar sitios", error);
    const siteId = currentUserSiteId();
    if (siteId) {
      select.innerHTML = `<option value="${siteId}" selected>Sitio ${siteId}</option>`;
      state.sitesById[String(siteId)] = `Sitio ${siteId}`;
    }
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

function getPreviousPeriod(from, to) {
  if (!from || !to) return { from, to };
  const start = new Date(from);
  const end = new Date(to);
  const ms = end.getTime() - start.getTime();
  const prevEnd = new Date(start.getTime() - 24 * 60 * 60 * 1000);
  const prevStart = new Date(prevEnd.getTime() - ms);
  return {
    from: formatDateISO(prevStart),
    to: formatDateISO(prevEnd),
  };
}

function formatDelta(current, previous) {
  if (!previous || Number(previous) === 0) return { delta: null, label: "N/A" };
  const delta = ((Number(current || 0) - Number(previous || 0)) / Number(previous)) * 100;
  const sign = delta > 0 ? "+" : "";
  return { delta, label: `${sign}${delta.toFixed(1)}%` };
}

function formatKpi(value, opts = {}) {
  if (value === null || value === undefined || Number.isNaN(value)) return "—";
  if (opts.type === "percent") {
    const v = Number(value);
    return `${v.toFixed(1)}%`;
  }
  if (opts.type === "number") {
    return Number(value).toLocaleString("es-MX", { maximumFractionDigits: 1 });
  }
  return value;
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

function countDaysInRange(from, to) {
  if (!from || !to) return 0;
  const start = new Date(from);
  const end = new Date(to);
  const diff = end.getTime() - start.getTime();
  return Math.max(1, Math.round(diff / (1000 * 60 * 60 * 24)) + 1);
}

function buildDireccionComparisons(current, previous, filters, prevRange) {
  const energyCurrent = sumField(current, "total_energy_wh_sum");
  const energyPrev = sumField(previous, "total_energy_wh_sum");
  const daysCurrent = countDaysInRange(filters.from, filters.to);
  const daysPrev = countDaysInRange(prevRange.from, prevRange.to);
  const dailyCurrent = daysCurrent ? energyCurrent / daysCurrent : 0;
  const dailyPrev = daysPrev ? energyPrev / daysPrev : 0;
  const availabilityCurrent = avgField(current, "availability_pct_avg");
  const availabilityPrev = avgField(previous, "availability_pct_avg");
  const pfCurrent = avgField(current, "pf_compliance_pct_avg");
  const pfPrev = avgField(previous, "pf_compliance_pct_avg");
  const sitesCurrent = Array.isArray(current) ? current.length : 0;
  const sitesPrev = Array.isArray(previous) ? previous.length : 0;
  const perSiteCurrent = sitesCurrent ? energyCurrent / sitesCurrent : 0;
  const perSitePrev = sitesPrev ? energyPrev / sitesPrev : 0;

  return [
    {
      label: "Consumo total",
      current: energyCurrent,
      previous: energyPrev,
      unit: "kWh",
    },
    {
      label: "Consumo diario prom.",
      current: dailyCurrent,
      previous: dailyPrev,
      unit: "kWh/día",
    },
    {
      label: "Consumo por sitio",
      current: perSiteCurrent,
      previous: perSitePrev,
      unit: "kWh/sitio",
    },
    {
      label: "Disponibilidad",
      current: availabilityCurrent,
      previous: availabilityPrev,
      type: "percent",
    },
    {
      label: "Cumplimiento PF",
      current: pfCurrent,
      previous: pfPrev,
      type: "percent",
    },
  ];
}

function getTopDevice(rows) {
  if (!Array.isArray(rows) || !rows.length) return null;
  const sorted = rows
    .slice()
    .sort(
      (a, b) =>
        (Number(b?.energy_wh_sum_sum) || 0) -
        (Number(a?.energy_wh_sum_sum) || 0)
    );
  const top = sorted[0];
  if (!top) return null;
  const label = deviceLabel(top.device_id, top.device_name, top.site_id);
  return {
    label,
    value: Number(top.energy_wh_sum_sum) || 0,
  };
}

function buildMantenimientoComparisons(current, previous, filters, prevRange) {
  const energyCurrent = sumField(current, "energy_wh_sum_sum");
  const energyPrev = sumField(previous, "energy_wh_sum_sum");
  const devicesCurrent = Array.isArray(current) ? current.length : 0;
  const devicesPrev = Array.isArray(previous) ? previous.length : 0;
  const perDeviceCurrent = devicesCurrent ? energyCurrent / devicesCurrent : 0;
  const perDevicePrev = devicesPrev ? energyPrev / devicesPrev : 0;
  const powerCurrent = avgField(current, "power_w_avg_avg");
  const powerPrev = avgField(previous, "power_w_avg_avg");
  const topCurrent = getTopDevice(current);
  const topPrev = getTopDevice(previous);

  return [
    {
      label: "Consumo total",
      current: energyCurrent,
      previous: energyPrev,
      unit: "kWh",
    },
    {
      label: "Consumo prom. por dispositivo",
      current: perDeviceCurrent,
      previous: perDevicePrev,
      unit: "kWh",
    },
    {
      label: "Potencia promedio",
      current: powerCurrent,
      previous: powerPrev,
      unit: "W",
    },
    {
      label: topCurrent?.label ? `Mayor consumidor: ${topCurrent.label}` : "Mayor consumidor",
      current: topCurrent?.value ?? 0,
      previous: topPrev?.value ?? 0,
      unit: "kWh",
      note: topCurrent?.label,
    },
    {
      label: "Dispositivos monitoreados",
      current: devicesCurrent,
      previous: devicesPrev,
      type: "number",
    },
  ];
}

function renderComparisonCards(target, cards = []) {
  if (!target) return;
  if (!cards.length) {
    setMessage(target, "Sin datos comparables para este rango.");
    return;
  }
  target.innerHTML = "";
  cards.forEach((card) => {
    const { delta, label } = formatDelta(card.current, card.previous);
    const deltaClass =
      delta === null
        ? ""
        : delta > 0
        ? "is-positive"
        : delta < 0
        ? "is-negative"
        : "";
    const currentText = formatComparisonValue(card.current, card);
    const prevText = formatComparisonValue(card.previous, card);
    const note = card.note ? ` · ${card.note}` : "";
    const meta = `Actual: ${currentText} · Prev: ${prevText}${note}`;
    const el = document.createElement("div");
    el.className = "comparison-card";
    el.innerHTML = `
      <p class="comparison-card__label">${card.label}</p>
      <p class="comparison-card__delta${deltaClass ? ` ${deltaClass}` : ""}">${label}</p>
      <p class="comparison-card__meta">${meta}</p>
    `;
    target.appendChild(el);
  });
}

function formatComparisonValue(value, card) {
  if (value === null || value === undefined || Number.isNaN(value)) return "—";
  if (card.type === "percent") return formatPercent(value);
  if (card.type === "number") {
    return formatKpi(value, { type: "number" });
  }
  if (card.unit === "W") {
    return `${Number(value || 0).toLocaleString("es-MX", {
      maximumFractionDigits: 1,
    })} W`;
  }
  if (card.unit === "kWh/día" || card.unit === "kWh/sitio") {
    return `${Number(value || 0).toLocaleString("es-MX", {
      maximumFractionDigits: 1,
    })} ${card.unit}`;
  }
  if (card.unit === "kWh") {
    return `${Number(value || 0).toLocaleString("es-MX", {
      maximumFractionDigits: 1,
    })} kWh`;
  }
  return formatKpi(value, { type: "number" });
}

function findMaxLabel(rows, field) {
  if (!Array.isArray(rows) || !rows.length) return null;
  const sorted = rows
    .slice()
    .sort((a, b) => (Number(b?.[field]) || 0) - (Number(a?.[field]) || 0));
  const top = sorted[0];
  const siteId = top?.site_id || top?.device_id;
  if (!siteId) return null;
  if (top?.device_name) {
    const siteName = state.sitesById[String(top.site_id)];
    return appendSiteAcronymOnce(top.device_name, siteName);
  }
  if (top?.device_id) {
    return deviceLabel(top.device_id, top.device_name, top.site_id);
  }
  if (top?.site_name) return top.site_name;
  const siteName = state.sitesById[String(siteId)];
  if (siteName) return siteName;
  return deviceLabel(siteId);
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
  next.margin = layout.margin || { l: 36, r: 48, t: 12, b: 36 };
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

function deviceLabel(deviceId, deviceName, siteId) {
  const meta = state.deviceMetaById[String(deviceId)];
  const siteName = meta?.siteName || state.sitesById[String(siteId)];
  const baseName =
    deviceName || meta?.name || state.devicesById[String(deviceId)];
  if (!baseName) return `Sensor ${deviceId}`;
  if (shouldAppendAcronym()) {
    return appendSiteAcronymOnce(baseName, siteName || meta?.siteName);
  }
  return baseName;
}

async function ensureDeviceLabels(siteId) {
  const key = siteId || "ALL";
  if (state.devicesById.__loadedFor === key) return;
  try {
    const payload = {
      table: "devices",
      select_columns: ["site_id", "device_id", "device_name"],
    };
    if (siteId && siteId !== "ALL") {
      payload.filter_map = { site_id: "=" + siteId };
    }
    const rows = await fetchDB(payload);
    const list = Array.isArray(rows?.data)
      ? rows.data
      : Array.isArray(rows)
      ? rows
      : [];
    list.forEach((row) => {
      if (!row.device_id || !row.device_name) return;
      const deviceKey = String(row.device_id);
      const siteName = state.sitesById[String(row.site_id)];
      state.devicesById[deviceKey] = row.device_name; // raw name
      state.deviceMetaById[deviceKey] = {
        name: row.device_name,
        siteName,
        siteId: row.site_id,
      };
    });
    state.devicesById.__loadedFor = key;
  } catch (error) {
    console.warn("reports: no se pudieron cargar nombres de dispositivos", error);
  }
}

async function ensureDeviceMetaForIds(ids = []) {
  const missing = Array.from(
    new Set(
      (ids || [])
        .filter(Boolean)
        .map(String)
        .filter((id) => !state.deviceMetaById[id])
    )
  );
  if (!missing.length) return;
  try {
    const rows = await fetchDB({
      table: "devices",
      filter_map: { device_id: missing },
      select_columns: ["site_id", "device_id", "device_name"],
    });
    const list = Array.isArray(rows?.data)
      ? rows.data
      : Array.isArray(rows)
      ? rows
      : [];
    list.forEach((row) => {
      if (!row.device_id) return;
      const deviceKey = String(row.device_id);
      const siteName = state.sitesById[String(row.site_id)];
      const baseName = row.device_name || `Sensor ${deviceKey}`;
      state.devicesById[deviceKey] = baseName;
      state.deviceMetaById[deviceKey] = {
        name: baseName,
        siteName,
        siteId: row.site_id,
      };
    });
  } catch (error) {
    console.warn("reports: no se pudo completar meta de dispositivos", error);
  }
}

function appendSiteAcronym(name, siteName) {
  if (!siteName) return name;
  const words = siteName.trim().split(/\s+/);
  const acronym = words.map((w) => w[0]?.toUpperCase()).join("");
  const dotted = acronym ? acronym.split("").join(".") + "." : "";
  return `${name} ${dotted}`.trim();
}

function appendSiteAcronymOnce(name, siteName) {
  if (!siteName || !shouldAppendAcronym()) return name;
  const acr = appendSiteAcronym("", siteName).trim();
  if (acr && name.includes(acr)) return name; // already has acronym appended
  return appendSiteAcronym(name, siteName);
}

function augmentDeviceMapping(mapping = {}) {
  // When viewing all sites, append site acronym to device labels in mapping
  const append = shouldAppendAcronym();
  if (!append) return mapping;
  const next = { ...mapping };
  if (next.device_id && typeof next.device_id === "object") {
    next.device_id = Object.fromEntries(
      Object.entries(next.device_id).map(([id, label]) => {
        const meta = state.deviceMetaById[String(id)];
        const siteName = meta?.siteName;
        const base = meta?.name || label || `Sensor ${id}`;
        return [id, appendSiteAcronymOnce(base, siteName)];
      })
    );
  }
  return next;
}

function shouldAppendAcronym() {
  return !state.currentSiteId || state.currentSiteId === "ALL";
}

function mapCategoricalAxisToLabels(figure) {
  if (!figure || !Array.isArray(figure.data)) return;
  const mapValue = (val, siteIdHint) => {
    if (state.sitesById[String(val)]) return state.sitesById[String(val)];
    const meta = state.deviceMetaById[String(val)];
    if (meta) {
      const name = meta.name || state.devicesById[String(val)] || `Sensor ${val}`;
      return shouldAppendAcronym() ? appendSiteAcronym(name, meta.siteName) : name;
    }
    if (state.devicesById[String(val)]) {
      const name = state.devicesById[String(val)];
      return shouldAppendAcronym()
        ? appendSiteAcronym(name, state.sitesById[String(siteIdHint)])
        : name;
    }
    if (siteIdHint && state.sitesById[String(siteIdHint)]) {
      return appendSiteAcronym(String(val), state.sitesById[String(siteIdHint)]);
    }
    return val;
  };

  figure.data.forEach((trace) => {
    if (Array.isArray(trace.x)) {
      trace.x = trace.x.map((val, idx) => mapValue(val, trace.site_id?.[idx]));
      if (trace.type === "bar" && (!trace.orientation || trace.orientation === "v")) {
        figure.layout = figure.layout || {};
        figure.layout.xaxis = figure.layout.xaxis || {};
        figure.layout.xaxis.tickangle = figure.layout.xaxis.tickangle ?? -30;
      }
    }
    // Also map legend names already handled by applyMapping; no change here.
  });
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
    const exportNode = buildExportNode(shell, area);
    document.body.appendChild(exportNode);
    const canvas = await window.html2canvas(exportNode, {
      scale: 2.2,
      useCORS: true,
      backgroundColor: "#ffffff",
      scrollX: 0,
      scrollY: 0,
    });
    document.body.removeChild(exportNode);
    const imgData = canvas.toDataURL("image/png");
    const margin = 18; // mm
    const pdf = new window.jspdf.jsPDF("p", "mm", "a4");
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const printableWidth = pageWidth - margin * 2;
    const ratio = printableWidth / canvas.width;
    const imgWidth = printableWidth;
    const imgHeight = canvas.height * ratio;
    let heightLeft = imgHeight;
    let position = margin;

    pdf.addImage(imgData, "PNG", margin, position, imgWidth, imgHeight);
    heightLeft -= pageHeight - margin * 2;

    while (heightLeft > 0) {
      position = margin - (imgHeight - heightLeft);
      pdf.addPage();
      pdf.addImage(imgData, "PNG", margin, position, imgWidth, imgHeight);
      heightLeft -= pageHeight - margin * 2;
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

function buildExportNode(shell, area) {
  const clone = shell.cloneNode(true);
  clone.classList.add("report-export");
  clone.querySelectorAll("[data-pdf-exclude]").forEach((el) => el.remove());
  clone.querySelectorAll(".report-card").forEach((el) =>
    el.classList.add("report-card--export")
  );
  const grid = clone.querySelector(".report-grid");
  if (grid) {
    grid.classList.add("report-grid--export");
  }

  const headerText = clone.querySelector(".report-header__text");
  if (headerText) {
    const kind = document.createElement("p");
    kind.className = "report-kind-print";
    kind.textContent = `Reporte: ${AREA_LABELS[area] || ""}`;
    headerText.appendChild(kind);
  }

  const footnote = document.createElement("div");
  footnote.className = "report-footnote";
  footnote.textContent = `MEST ENERGY — ${formatToday()}`;
  clone.appendChild(footnote);

  // Add filters summary for clarity in PDF
  const filterSummary = document.createElement("div");
  filterSummary.className = "report-footnote";
  filterSummary.textContent = `Filtros: ${describeRange({
    from: document.getElementById("report-from")?.value,
    to: document.getElementById("report-to")?.value,
  })} · Sitio: ${siteLabel(resolveSite(document.getElementById("report-site")))}`;
  clone.insertBefore(filterSummary, footnote);

  clone.style.position = "absolute";
  clone.style.left = "-9999px";
  clone.style.top = "0";
  clone.style.width = "182mm"; // slightly narrower than printable width to keep margins
  clone.style.padding = "12mm 14mm 16mm";
  clone.style.background = "#ffffff";
  return clone;
}
