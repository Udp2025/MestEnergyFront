/*********************************************************************
 *  Histogram page – matching API & UX conventions of heat_map.js
 *********************************************************************/
import Plotly from "plotly.js-dist-min";
import debounce from "lodash.debounce";
import { fetchPlot, applyMapping } from "../utils/plot";
import { fillSelect } from "../utils/list";
import { getSites, getDevices } from "../utils/core";

document.addEventListener("DOMContentLoaded", async () => {
  const $ = (id) => document.getElementById(id);

  /* ---------- DOM elements -------------------------------------- */
  const form = $("plot-filters");
  const chartDiv = $("histChart");
  const runBtn = $("run");

  const siteSel = $("site"); // undefined for non‑admins
  const deviceSel = $("device");
  const metricSel = $("metric");
  const aggSel = $("agg");
  const fromInp = $("from");
  const toInp = $("to");
  const binsInp = $("bins");

  /* ---------- Site / device cascading --------------------------- */
  let activeSiteId = window.currentUserIsAdmin ? null : Number(window.currentSiteId);

  async function loadSites() {
    if (!window.currentUserIsAdmin) return;
    const sites = await getSites();
    fillSelect(siteSel, sites, "site_id", "site_name");
    activeSiteId = siteSel.value;
  }

  async function loadDevices() {
    const rows = await getDevices(activeSiteId);
    fillSelect(deviceSel, rows, "device_id", "device_name");
    runBtn.disabled = deviceSel.options.length === 0;
  }

  if (window.currentUserIsAdmin) {
    await loadSites();
    siteSel.onchange = async () => {
      activeSiteId = siteSel.value;
      await loadDevices(); // no draw here
    };
  }
  await loadDevices();

  /* ---------- CONSTANTS ------------------------------------------ */
  const HOUR_WINDOW = "H"; // backend’s smallest valid bucket
  const RAW_FN = "raw"; // special option we’ll add to <select>

  function buildBody() {
    const metric = metricSel.value;
    const fn = aggSel.value; // avg, sum, … or 'raw'
    const start = `${fromInp.value} 00:00:00`;
    const end = `${toInp.value} 23:59:59`;

    const body = {
      table: "measurements",
      filter_map: {
        measurement_time: `[${start}, ${end}]`,
        site_id: "=" + activeSiteId,
        device_id: "=" + deviceSel.value,
      },
      //  We’ll add chart later
    };

    if (fn !== RAW_FN) {
      body.aggregation = [
        {
          aggregations: { [metric]: [fn] },
          time_window: HOUR_WINDOW,
          time_column: "measurement_time",
        },
      ];
    }
    /*  else → leave body.aggregation undefined  */

    body.chart = {
      chart_type: "histogram",
      x: fn === RAW_FN ? metric : `${metric}_${fn}`,
      nbinsx: binsInp.value ? Number(binsInp.value) : undefined,
    };

    return body;
  }

  /* ---------- Draw ------------------------------------------------ */
  async function draw() {
    if (runBtn.disabled) return;
    runBtn.disabled = true;
    try {
      const { figure, config, mapping } = await fetchPlot(buildBody());

      /* 2️ Inject nbinsx client‑side if the input is filled  */
      if (binsInp.value) {
        const maxBins = Number(binsInp.value);
        figure.data.forEach((t) => {
          if (t.type === "histogram") t.nbinsx = maxBins;
        });
      }
      applyMapping(figure, mapping); // preserve sensor names, units, etc.
      await Plotly.react(chartDiv, figure.data, figure.layout, config);
    } catch (err) {
      alert("No se pudo cargar el histograma: " + err.message);
      console.error(err);
    } finally {
      runBtn.disabled = false;
    }
  }

  /* ---------- Events --------------------------------------------- */
  form.addEventListener(
    "input",
    debounce(() => {
      // enable Run only when mandatory fields are populated
      runBtn.disabled = !fromInp.value || !toInp.value || !deviceSel.value;
    }, 300)
  );

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    draw();
  });

  /* ---------- Defaults ------------------------------------------- */
  const todayISO = new Date().toISOString().slice(0, 10);
  fromInp.value = todayISO;
  toInp.value = todayISO;
});
