/* ------------------------------------------------------------------ */
/*  Scatter‑plot view – colours, dynamic aggregation & safe defaults  */
/* ------------------------------------------------------------------ */
import { fetchPlot, applyMapping } from "../utils/plot";
import Plotly from "plotly.js-dist-min";
import { fillSelect } from "../utils/list";
import { getSites, getDevices, fmtDate } from "../utils/core";
import {
  canViewAllSites,
  currentUserSiteId,
  ensureAuthenticatedOrRedirect,
} from "../utils/auth";

/* ---------- Dates ------------------------------------------------- */
const TODAY_DATE = new Date();
const LAST_WEEK_DATE = new Date(TODAY_DATE.getTime() - 7 * 24 * 60 * 60 * 1e3);
const TODAY = fmtDate(TODAY_DATE);
const LAST_WEEK = fmtDate(LAST_WEEK_DATE);

/* ---------- Defaults --------------------------------------------- */
const DEFAULTS = {
  metric1: "current_a",
  metric2: "voltage_v",
  from: LAST_WEEK,
  to: TODAY,
  freq: "5min",
  agg1: "original",
  agg2: "original",
  color_by: "device_id",
};

/* ------------------------------------------------------------------ */
/*  Everything lives inside DOMContentLoaded                          */
/* ------------------------------------------------------------------ */
document.addEventListener("DOMContentLoaded", async () => {
  ensureAuthenticatedOrRedirect();
  const $ = (id) => document.getElementById(id);

  /* ----- grab DOM nodes ------------------------------------------ */
  const runBtn = $("run");
  const form = $("plot-filters");
  const chart = $("scatterChart");
  if (!form || !chart) {
    console.error("scatter.js: required DOM nodes not found");
    return;
  }

  const siteSel = $("site"); // undefined for non‑admins
  const deviceSel = $("device");
  const freqSel = $("freq");
  const agg1Sel = $("agg1");
  const agg2Sel = $("agg2");
  const colorSel = $("color_by"); // << new selector

  /* ----- Site / device dropdowns --------------------------------- */
  const isAdmin = canViewAllSites();
  let activeSiteId = isAdmin ? null : currentUserSiteId();

  async function loadSites() {
    if (!isAdmin) return;
    const sites = await getSites();
    fillSelect(siteSel, sites, "site_id", "site_name");
    activeSiteId = siteSel.value;
  }

  async function loadDevices() {
    if (!deviceSel) return;
    if (!activeSiteId) {
      fillSelect(deviceSel, [], "device_id", "device_name");
      runBtn.disabled = true;
      return;
    }
    const rows = await getDevices(activeSiteId);
    fillSelect(deviceSel, rows, "device_id", "device_name");

    /* prepend an “ALL” option so colour‑by‑device makes sense      */
    if (rows.length > 0) {
      deviceSel.insertAdjacentHTML("afterbegin", '<option value="ALL">Todos</option>');
      deviceSel.value = "ALL";
    }

    runBtn.disabled = rows.length === 0;
  }

  try {
    if (isAdmin) {
      await loadSites();
      siteSel?.addEventListener("change", async () => {
        activeSiteId = siteSel.value;
        await loadDevices();
      });
    }
    if (!isAdmin && !activeSiteId) {
      throw new Error("El usuario no tiene un sitio asignado.");
    }
    await loadDevices();
  } catch (err) {
    console.error(err);
    alert("No se pudieron cargar los dispositivos/sitios: " + (err?.message || err));
    runBtn.disabled = true;
    return; // bail early
  }

  /* ----- Raw‑data logic: lock aggregation controls --------------- */
  function toggleAggControls() {
    const raw = freqSel.value === "5min";
    agg1Sel.disabled = raw; // disabled controls are not posted
    agg2Sel.disabled = raw;
    if (raw) {
      agg1Sel.value = agg2Sel.value = "original";
    }
  }
  toggleAggControls();
  freqSel.addEventListener("change", toggleAggControls);

  const SHORT_FREQS = ["5min", "H", "2H", "4H", "6H", "12H"]; // allowed for “hour”

  function adjustColorSelector() {
    const allowHour = SHORT_FREQS.includes(freqSel.value);

    // enable/disable only the <option value="hour">
    const hourOpt = colorSel.querySelector('option[value="hour"]');
    if (hourOpt) hourOpt.disabled = !allowHour; // MDN: option.disabled

    // if “hour” became invalid while selected → fall back
    if (!allowHour && colorSel.value === "hour") {
      colorSel.value = "device_id";
    }
  }

  adjustColorSelector(); // run once at load
  freqSel.addEventListener("change", adjustColorSelector);

  /* ----- Helper --------------------------------------------------- */
  const v = (name) => form[name]?.value?.trim() || DEFAULTS[name];

  /* ----- Payload builder ----------------------------------------- */
  function buildBody() {
    const metric1 = v("metric1");
    const metric2 = v("metric2");
    const func1 = v("agg1");
    const func2 = v("agg2");
    const freqRaw = v("freq");
    const freq = freqRaw === "5min" ? null : freqRaw;
    const from = v("from");
    const to = v("to");
    const colorBy = v("color_by");

    /* final column names */
    const metricFunc1 = func1 === "original" ? metric1 : `${metric1}_${func1}`;
    const metricFunc2 = func2 === "original" ? metric2 : `${metric2}_${func2}`;

    /* need aggregation? */
    const needsAgg = !(freqRaw === "5min" && func1 === "original" && func2 === "original");

    /* device filter – omit when “ALL” so every device is included  */
    const filterMap = {
      measurement_time: `[${from} 00:00:00, ${to} 23:59:59]`,
      site_id: "=" + activeSiteId,
      ...(deviceSel.value !== "ALL" && { device_id: "=" + deviceSel.value }),
    };

    // Single device being displayed and color === device_id
    const colorDict =
      colorSel.value === "device_id" && deviceSel.value !== "ALL"
        ? { opacity: 0.7 }
        : { color: colorBy, symbol: colorBy, opacity: 0.7 };

    const aggDict =
      metric1 !== metric2
        ? {
            [metric1]: [func1],
            [metric2]: [func2],
          }
        : {
            [metric1]: [func1, func2],
          };

    return {
      table: "measurements",
      filter_map: filterMap,
      ...(needsAgg && {
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: aggDict,
            time_window: freq,
            time_column: "measurement_time",
          },
        ],
      }),
      chart: {
        chart_type: "scatter",
        x: metricFunc1,
        y: metricFunc2,
        style: colorDict,
      },
    };
  }

  /* ----- Submit handler ------------------------------------------ */
  async function run(e) {
    e?.preventDefault();
    if (runBtn.disabled) return;
    // basic date guard
    const from = v("from");
    const to = v("to");
    if (from > to) {
      alert("Rango de fechas inválido: 'Desde' es mayor que 'Hasta'.");
      return;
    }
    runBtn.disabled = true; // UX guard
    try {
      const { figure, config, mapping } = await fetchPlot(buildBody());
      applyMapping(figure, mapping);
      await Plotly.react(chart, figure.data, figure.layout, config); // efficient re‑draw
    } catch (err) {
      console.error(err);
      alert("No se pudo cargar el gráfico: " + (err?.message || err));
    } finally {
      runBtn.disabled = false;
    }
  }

  form.addEventListener("submit", run);

  if (!runBtn.disabled) {
    await run();
  }
});
