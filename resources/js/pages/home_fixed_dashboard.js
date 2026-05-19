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

const normalizeText = (value) =>
  String(value || "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase();

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
    return {
      table: "measurements",
      filter_map: {
        measurement_time: todayRange(),
        ...(siteId ? { site_id: "=" + siteId } : {}),
      },
      aggregation: [
        {
          group_by: ["site_id", "device_id"],
          aggregations: { energy_wh: ["sum"] },
          time_window: "H",
          time_column: "measurement_time",
        },
      ],
      chart: {
        chart_type: "line",
        x: "measurement_time",
        y: "energy_wh_sum",
        style: { color: "device_id" },
      },
    };
  }

  function mapRows(response) {
    return Array.isArray(response?.data)
      ? response.data
      : Array.isArray(response)
      ? response
      : [];
  }

  function groupEnergyByTimestamp(rows, generationIds) {
    const grouped = new Map();

    rows.forEach((row) => {
      const timestamp = row?.measurement_time;
      const deviceId = Number(row?.device_id);
      const value = Number(row?.energy_wh_sum);

      if (!timestamp || !Number.isFinite(value)) return;

      if (!grouped.has(timestamp)) {
        grouped.set(timestamp, {
          consumption: 0,
          generation: 0,
        });
      }

      const bucket = grouped.get(timestamp);
      if (generationIds.has(deviceId)) {
        bucket.generation += value;
      } else {
        bucket.consumption += value;
      }
    });

    return grouped;
  }

  function buildAggregatedFigure(grouped) {
    const timestamps = Array.from(grouped.keys()).sort(
      (a, b) => new Date(a) - new Date(b)
    );

    return {
      data: [
        {
          type: "scatter",
          mode: "lines+markers",
          name: "Consumo",
          x: timestamps,
          y: timestamps.map((timestamp) => grouped.get(timestamp)?.consumption ?? 0),
          hovertemplate: "Fecha: %{x}<br>Consumo: %{y:.0f} Wh<extra></extra>",
          line: { shape: "spline", color: "#ff8a73", width: 3 },
          marker: { color: "#ff8a73", size: 6 },
          fill: "tozeroy",
          fillcolor: "rgba(255, 138, 115, 0.12)",
          showlegend: true,
        },
        {
          type: "scatter",
          mode: "lines+markers",
          name: "Generación",
          x: timestamps,
          y: timestamps.map((timestamp) => grouped.get(timestamp)?.generation ?? 0),
          hovertemplate: "Fecha: %{x}<br>Generación: %{y:.0f} Wh<extra></extra>",
          line: { shape: "spline", color: "#3bc6e3", width: 3 },
          marker: { color: "#3bc6e3", size: 6 },
          fill: "tozeroy",
          fillcolor: "rgba(59, 198, 227, 0.12)",
          showlegend: true,
        },
      ],
      layout: {},
    };
  }

  function buildDeviceFigure(rows, deviceNameById) {
    const grouped = new Map();

    rows.forEach((row) => {
      const timestamp = row?.measurement_time;
      const deviceKey = String(row?.device_id ?? "");
      const value = Number(row?.energy_wh_sum);

      if (!timestamp || !deviceKey || !Number.isFinite(value)) return;

      if (!grouped.has(deviceKey)) {
        grouped.set(deviceKey, []);
      }

      grouped.get(deviceKey).push({
        x: timestamp,
        y: value,
      });
    });

    return {
      data: Array.from(grouped.entries()).map(([deviceKey, points]) => {
        points.sort((a, b) => new Date(a.x) - new Date(b.x));
        const label = deviceNameById.get(deviceKey) || deviceKey;

        return {
          type: "scatter",
          mode: "lines+markers",
          name: label,
          x: points.map((point) => point.x),
          y: points.map((point) => point.y),
          hovertemplate: "%{y:.0f} Wh<br>%{x}<extra>" + label + "</extra>",
          line: { shape: "spline" },
          showlegend: true,
        };
      }),
      layout: {},
    };
  }

  async function renderAggregatedChart() {
    const [devicesResponse, measurementsResponse] = await Promise.all([
      fetchDB({
        table: "devices",
        filter_map: {
          ...(siteId ? { site_id: "=" + siteId } : {}),
        },
        select_columns: ["device_id", "device_name"],
      }),
      fetchDB({
        table: "measurements",
        filter_map: {
          measurement_time: todayRange(),
          ...(siteId ? { site_id: "=" + siteId } : {}),
        },
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: { energy_wh: ["sum"] },
            time_window: "H",
            time_column: "measurement_time",
          },
        ],
      }),
    ]);

    const deviceRows = mapRows(devicesResponse);
    const measurementRows = mapRows(measurementsResponse);

    const generationIds = new Set(
      deviceRows
        .filter((row) => normalizeText(row?.device_name).includes("generacion"))
        .map((row) => Number(row?.device_id))
        .filter((id) => Number.isFinite(id))
    );

    const grouped = groupEnergyByTimestamp(measurementRows, generationIds);
    return buildAggregatedFigure(grouped);
  }

  async function renderDeviceChart() {
    const [devicesResponse, measurementsResponse] = await Promise.all([
      fetchDB({
        table: "devices",
        filter_map: {
          ...(siteId ? { site_id: "=" + siteId } : {}),
        },
        select_columns: ["device_id", "device_name"],
      }),
      fetchDB({
        table: "measurements",
        filter_map: {
          measurement_time: todayRange(),
          ...(siteId ? { site_id: "=" + siteId } : {}),
        },
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: { energy_wh: ["sum"] },
            time_window: "H",
            time_column: "measurement_time",
          },
        ],
      }),
    ]);

    const deviceRows = mapRows(devicesResponse);
    const measurementRows = mapRows(measurementsResponse);
    const deviceNameById = new Map(
      deviceRows.map((row) => [
        String(row?.device_id ?? ""),
        row?.device_name || String(row?.device_id ?? ""),
      ])
    );

    return buildDeviceFigure(measurementRows, deviceNameById);
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
      let fig = { data: [], layout: {} };
      let cfg = {};

      if (mode === "agg") {
        fig = await renderAggregatedChart();
      } else {
        fig = await renderDeviceChart();
      }
      tuneLayout(fig);
      if (plotIsEmpty(fig)) {
        setStatus("Sin datos para hoy.", "info");
      } else {
        setStatus("", "success");
      }
      await Plotly.react(
        chartEl,
        fig.data,
        {
          ...fig.layout,
        },
        {
          ...cfg,
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
      const [daily, hourly, energyAgg] = await Promise.all([
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
        fetchDB({
          table: "device_daily_kpi",
          filter_map: {
            site_id: "=" + siteId,
            kpi_date: [todayISO()],
          },
          aggregation: [
            {
              group_by: ["site_id"],
              aggregations: {
                energy_wh_sum: ["sum"],
              },
            },
          ],
        }),
      ]);

      const dailyRow = (Array.isArray(daily?.data) ? daily.data : []).find(
        (row) => String(row.site_id) === String(siteId)
      );
      const pf = dailyRow?.pf_compliance_pct ?? null;
      const availability = dailyRow?.availability_pct ?? null;
      const energyAggRow = (
        Array.isArray(energyAgg?.data) ? energyAgg.data : []
      )
        .filter((row) => String(row.site_id) === String(siteId))
        .shift();
      const energy =
        energyAggRow?.energy_wh_sum_sum ?? dailyRow?.total_energy_wh ?? null;

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
    if (!figure) return;
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
    if (mode === "agg") {
      figure.layout.hovermode = "x unified";
      figure.layout.legend = {
        ...(figure.layout.legend || {}),
        orientation: "h",
        x: 0.5,
        xanchor: "center",
        y: -0.2,
        yanchor: "top",
      };
      return;
    }

    figure.layout.legend = {
      ...(figure.layout.legend || {}),
      orientation: "h",
      x: 0,
      y: -0.2,
    };
  }

  init();
});
