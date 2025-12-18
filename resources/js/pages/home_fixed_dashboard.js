import Plotly from "plotly.js-dist-min";
import {
  fetchPlot,
  applyMapping,
  normalisePlotError,
  plotIsEmpty,
} from "../utils/plot";
import { fetchDB, getSites } from "../utils/core";
import { canViewAllSites, currentUserSiteId } from "../utils/auth";
import { fillSelect } from "../utils/list";

const todayISO = () => new Date().toISOString().slice(0, 10);
const todayRange = () => `[${todayISO()} 00:00:00, ${todayISO()} 23:59:59]`;

const formatPercent = (value) => {
  if (value === null || value === undefined || Number.isNaN(value)) return "—";
  const normalized = value <= 1 ? value * 100 : value;
  return `${normalized.toFixed(1)}%`;
};

const formatNumber = (value) => {
  if (value === null || value === undefined || Number.isNaN(value)) return "—";
  if (Math.abs(value) >= 1000) {
    return `${(value / 1000).toFixed(1)}k`;
  }
  return `${value}`;
};

const formatEnergy = (value) => {
  if (value === null || value === undefined || Number.isNaN(value)) return "—";
  if (Math.abs(value) >= 1_000_000) {
    return `${(value / 1_000_000).toFixed(2)} MWh`;
  }
  if (Math.abs(value) >= 1_000) {
    return `${(value / 1_000).toFixed(1)} kWh`;
  }
  return `${Number(value).toFixed(0)} Wh`;
};

document.addEventListener("DOMContentLoaded", () => {
  const root = document.querySelector("[data-fixed-dashboard]");
  if (!root) return;

  const chartEl = root.querySelector("[data-dashboard-chart]");
  const statusEl = root.querySelector("[data-chart-status]");
  const modeButtons = Array.from(root.querySelectorAll("[data-mode]"));
  const siteSelect = root.querySelector("[data-site-select]");
  const kpiNodes = {
    pf: root.querySelector("[data-kpi-value='pf']"),
    availability: root.querySelector("[data-kpi-value='availability']"),
    activeDevices: root.querySelector("[data-kpi-value='activeDevices']"),
    energy: root.querySelector("[data-kpi-value='energy']"),
  };

  const isSuperAdmin = root.dataset.superAdmin === "1" || canViewAllSites();
  let siteId = root.dataset.siteId || currentUserSiteId();
  let mode = "agg";

  function setStatus(message, type = "info") {
    if (!statusEl) return;
    statusEl.textContent = message;
    statusEl.dataset.status = type;
  }

  async function populateSites() {
    if (!isSuperAdmin || !siteSelect) return;
    try {
      const sites = await getSites();
      fillSelect(siteSelect, sites, "site_id", "site_name");
      if (!siteId && siteSelect.value) {
        siteId = siteSelect.value;
      } else if (siteId) {
        siteSelect.value = siteId;
      }
      siteSelect.disabled = false;
    } catch (error) {
      console.error("home dashboard: unable to load sites", error);
      setStatus("No se pudieron cargar los sitios.", "error");
      siteSelect.disabled = false;
    }
  }

  function buildChartPayload() {
    const groupBy = mode === "deagg" ? ["site_id", "device_id"] : ["site_id"];
    const style =
      mode === "deagg" ? { color: "device_id" } : { color: "site_id" };
    return {
      table: "measurements",
      filter_map: {
        measurement_time: todayRange(),
        ...(siteId ? { site_id: "=" + siteId } : {}),
      },
      aggregation: [
        {
          group_by: groupBy,
          aggregations: { energy_wh: ["sum"] },
          time_window: "H",
          time_column: "measurement_time",
        },
      ],
      chart: {
        chart_type: "line",
        x: "measurement_time",
        y: "energy_wh_sum",
        style,
      },
    };
  }

  async function renderChart() {
    if (!chartEl) return;
    if (!siteId && isSuperAdmin) {
      setStatus("Selecciona un sitio para ver la serie.", "info");
      return;
    }
    chartEl.style.height = "480px";
    try {
      setStatus("Cargando serie…", "info");
      const { figure, config, mapping } = await fetchPlot(buildChartPayload());
      applyMapping(figure, mapping);
      tuneLayout(figure);
      if (plotIsEmpty(figure)) {
        setStatus("Sin datos para hoy.", "info");
      } else {
        setStatus("", "success");
      }
      await Plotly.react(
        chartEl,
        figure.data,
        {
          ...figure.layout,
        },
        {
          ...config,
          responsive: true,
          displayModeBar: false,
        }
      );
    } catch (error) {
      console.error("home dashboard: chart error", error);
      const { message, severity } = normalisePlotError(error);
      setStatus(message, severity);
    }
  }

  async function fetchDailyKpis() {
    if (!siteId) {
      Object.values(kpiNodes).forEach(
        (node) => node && (node.textContent = "—")
      );
      return;
    }
    try {
      const [daily, hourly] = await Promise.all([
        fetchDB({
          table: "site_daily_kpi",
          filter_map: {
            site_id: "=" + siteId,
            kpi_date: [todayISO()],
          },
          select_columns: [
            "site_id",
            "kpi_date",
            "pf_compliance_pct",
            "availability_pct",
            "total_energy_wh",
          ],
        }),
        fetchDB({
          table: "site_hourly_kpi",
          filter_map: {
            site_id: "=" + siteId,
            hour_start: todayRange(),
          },
          select_columns: ["site_id", "hour_start", "active_devices"],
        }),
      ]);

      const dailyRow = (Array.isArray(daily?.data) ? daily.data : []).find(
        (row) => String(row.site_id) === String(siteId)
      );
      const pf = dailyRow?.pf_compliance_pct ?? null;
      const availability = dailyRow?.availability_pct ?? null;
      const energy = dailyRow?.total_energy_wh ?? null;

      const hourlyRows = Array.isArray(hourly?.data) ? hourly.data : [];
      const latestHour = hourlyRows
        .filter((row) => row.hour_start)
        .sort((a, b) =>
          String(b.hour_start).localeCompare(String(a.hour_start))
        )[0];
      const active = latestHour?.active_devices ?? null;

      if (kpiNodes.pf) kpiNodes.pf.textContent = formatPercent(Number(pf));
      if (kpiNodes.availability)
        kpiNodes.availability.textContent = formatPercent(Number(availability));
      if (kpiNodes.energy)
        kpiNodes.energy.textContent = formatEnergy(Number(energy));
      if (kpiNodes.activeDevices)
        kpiNodes.activeDevices.textContent = formatNumber(Number(active));
    } catch (error) {
      console.error("home dashboard: kpi error", error);
      if (kpiNodes.pf) kpiNodes.pf.textContent = "—";
      if (kpiNodes.availability) kpiNodes.availability.textContent = "—";
      if (kpiNodes.energy) kpiNodes.energy.textContent = "—";
      if (kpiNodes.activeDevices) kpiNodes.activeDevices.textContent = "—";
      setStatus("No se pudieron cargar los KPIs.", "error");
    }
  }

  function bindEvents() {
    if (siteSelect) {
      siteSelect.addEventListener("change", () => {
        siteId = siteSelect.value || null;
        fetchDailyKpis();
        renderChart();
      });
    }
    modeButtons.forEach((btn) => {
      btn.addEventListener("click", () => {
        modeButtons.forEach((b) => b.classList.remove("is-active"));
        btn.classList.add("is-active");
        mode = btn.dataset.mode === "deagg" ? "deagg" : "agg";
        renderChart();
      });
    });
  }

  async function init() {
    await populateSites();
    bindEvents();
    await Promise.all([renderChart(), fetchDailyKpis()]);
  }

  function tuneLayout(figure) {
    if (!figure.layout) figure.layout = {};
    figure.layout.title = "";
    figure.layout.autosize = true;
    figure.layout.margin = {
      l: 36,
      r: 10,
      t: 8,
      b: 80,
      pad: 0,
    };
    figure.layout.xaxis = {
      ...(figure.layout.xaxis || {}),
      title: "",
      automargin: true,
    };
    figure.layout.yaxis = {
      ...(figure.layout.yaxis || {}),
      title: "",
      automargin: true,
    };
    figure.layout.legend = {
      ...(figure.layout.legend || {}),
      orientation: "h",
      x: 0,
      y: -0.2,
    };
  }

  init();
});
