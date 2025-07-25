/* ------------------------------------------------------------------ */
/*  histogram‑plot view – colours, dynamic aggregation & safe defaults  */
/* ------------------------------------------------------------------ */
import { fetchPlot, applyMapping } from "../utils/plot";
import Plotly from "plotly.js-dist-min";
import { fillSelect } from "../utils/list";
import { getSites, getDevices, fmtDate } from "../utils/core";

/* ---------- Dates ------------------------------------------------- */
const TODAY_DATE = new Date();
const LAST_WEEK_DATE = new Date(TODAY_DATE.getTime() - 7 * 24 * 60 * 60 * 1e3);
const TODAY = fmtDate(TODAY_DATE);
const LAST_WEEK = fmtDate(LAST_WEEK_DATE);

/* ---------- Defaults --------------------------------------------- */
const DEFAULTS = {
  metric1: "current_a",
  from: LAST_WEEK,
  to: TODAY,
  freq: "5min",
  agg1: "original",
  bins: "",
};

/* ------------------------------------------------------------------ */
/*  Everything lives inside DOMContentLoaded                          */
/* ------------------------------------------------------------------ */
document.addEventListener("DOMContentLoaded", async () => {
  const $ = (id) => document.getElementById(id);

  /* ----- grab DOM nodes ------------------------------------------ */
  const runBtn = $("run");
  const form = $("plot-filters");
  const chart = $("histogramChart");
  if (!form || !chart) {
    console.error("histogram.js: required DOM nodes not found");
    return;
  }

  const siteSel = $("site"); // undefined for non‑admins
  const deviceSel = $("device");
  const freqSel = $("freq");
  const agg1Sel = $("agg1");
  const colorBy = "device_id";
  const bins = $("bins");

  /* ----- Site / device dropdowns --------------------------------- */
  let activeSiteId = window.currentUserIsAdmin ? null : Number(window.currentSiteId);

  async function loadSites() {
    if (!window.currentUserIsAdmin) return;
    const sites = await getSites();
    fillSelect(siteSel, sites, "site_id", "site_name");
    activeSiteId = Number(siteSel.value);
  }

  async function loadDevices() {
    const rows = await getDevices(activeSiteId);
    fillSelect(deviceSel, rows, "device_id", "device_name");

    /* prepend an “ALL” option so colour‑by‑device makes sense      */
    deviceSel.insertAdjacentHTML("afterbegin", '<option value="ALL">Todos</option>'); // MDN pattern
    deviceSel.value = "ALL";

    runBtn.disabled = deviceSel.options.length === 0;
  }

  if (window.currentUserIsAdmin) {
    await loadSites();
    colorBy = "site_id";
    siteSel.addEventListener("change", async () => {
      activeSiteId = Number(siteSel.value);
      await loadDevices();
    });
  }
  await loadDevices();

  /* ----- Raw‑data logic: lock aggregation controls --------------- */
  function toggleAggControls() {
    const raw = freqSel.value === "5min";
    agg1Sel.disabled = raw; // disabled controls are not posted
    if (raw) {
      agg1Sel.value = "original";
    }
  }
  toggleAggControls();
  freqSel.addEventListener("change", toggleAggControls);

  /* ----- Helper --------------------------------------------------- */
  const v = (name) => form[name]?.value?.trim() || DEFAULTS[name];

  /* ----- Payload builder ----------------------------------------- */
  function buildBody() {
    const metric1 = v("metric1");

    const func1 = v("agg1");

    const freqRaw = v("freq");
    const freq = freqRaw === "5min" ? null : freqRaw;
    const from = v("from");
    const to = v("to");

    /* final column names */
    const metricFunc1 = func1 === "original" ? metric1 : `${metric1}_${func1}`;

    /* need aggregation? */
    const needsAgg = !(freqRaw === "5min" && func1 === "original");

    /* device filter – omit when “ALL” so every device is included  */
    const filterMap = {
      measurement_time: `[${from} 00:00:00, ${to} 23:59:59]`,
      site_id: "=" + activeSiteId,
      ...(deviceSel.value !== "ALL" && { device_id: "=" + deviceSel.value }),
    };

    const colorDict = deviceSel.value === "ALL" ? { color: colorBy } : {};

    const nbinsDict = bins.value === "" ? {} : { nbins: parseInt(bins.value) };

    const propsDict = { ...nbinsDict, ...colorDict };
    console.log(propsDict);
    return {
      table: "measurements",
      filter_map: filterMap,
      ...(needsAgg && {
        aggregation: [
          {
            group_by: ["site_id", "device_id"],
            aggregations: { [metric1]: [func1] },
            time_window: freq,
            time_column: "measurement_time",
          },
        ],
      }),
      chart: {
        chart_type: "histogram",
        x: metricFunc1,
        style: propsDict,
      },
    };
  }

  /* ----- Submit handler ------------------------------------------ */
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (runBtn.disabled) return;
    runBtn.disabled = true; // UX guard
    try {
      const { figure, config, mapping } = await fetchPlot(buildBody());
      applyMapping(figure, mapping);
      await Plotly.react(chart, figure.data, figure.layout, config); // efficient re‑draw
    } finally {
      runBtn.disabled = false;
    }
  });
});
