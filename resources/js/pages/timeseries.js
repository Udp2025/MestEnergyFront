import { fetchPlot, applyMapping } from "../utils/plot";
import Plotly from "plotly.js-dist-min";
import {
  canViewAllSites,
  currentUserSiteId,
  ensureAuthenticatedOrRedirect,
} from "../utils/auth";
import { fillSelect } from "../utils/list";
import { getSites, getDevices } from "../utils/core";

const TODAY = new Date().toISOString().slice(0, 10);
const DEFAULTS = {
  metric: "power_w",
  from: TODAY,
  to: TODAY,
  period: "H",
  agg: "avg",
};

/* ------------------------------------------------------------------ */
/*  Everything lives inside DOMContentLoaded                          */
/* ------------------------------------------------------------------ */
document.addEventListener("DOMContentLoaded", () => {
  ensureAuthenticatedOrRedirect();
  const $ = (id) => document.getElementById(id);
  const runBtn = $("run");
  const form = $("plot-filters");
  const chart = $("lineChart");
  if (!form || !chart) {
    console.error("timeseries.js: required DOM nodes not found");
    return; // bail early, avoid further errors
  }

  const isAdmin = canViewAllSites();
  const siteSel = $("site");
  const deviceSel = $("device");
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
    if (rows.length > 0) {
      deviceSel.insertAdjacentHTML(
        "afterbegin",
        '<option value="ALL">Todos</option>'
      );
      deviceSel.value = "ALL";
    }
    runBtn.disabled = rows.length === 0;
  }

  const initPromise = (async () => {
    try {
      if (isAdmin) {
        await loadSites();
        siteSel.onchange = async () => {
          activeSiteId = siteSel.value;
          await loadDevices();
        };
      } else if (!activeSiteId) {
        throw new Error("El usuario no tiene un sitio asignado.");
      }
      await loadDevices();
    } catch (error) {
      console.error(error);
      alert("No se pudieron cargar sitios/dispositivos: " + (error?.message || error));
      runBtn.disabled = true;
      return;
    }
  })();

  /* -- helpers ----------------------------------------------------- */
  const v = (name) => form[name]?.value?.trim() || DEFAULTS[name];
  function buildBody() {
    const metric = v("metric");
    const period = v("period");
    const func = v("agg");
    const from = v("from");
    const to = v("to");

    console.log("fechas: ", `[${from} 00:00:00, ${to} 23:59:59]`);

    return {
      table: "measurements",
      filter_map: {
        measurement_time: `[${from} 00:00:00, ${to} 23:59:59]`,
        ...(deviceSel?.value && deviceSel.value !== "ALL"
          ? { device_id: "=" + deviceSel.value }
          : {}),
        ...(activeSiteId ? { site_id: "=" + activeSiteId } : {}),
      },
      aggregation: [
        {
          group_by: ["site_id", "device_id"],
          aggregations: { [metric]: [func] },
          time_window: period,
          time_column: "measurement_time",
        },
      ],
      chart: {
        chart_type: "line",
        x: "measurement_time",
        y: `${metric}_${func}`,
        style: { color: "device_id" },
      },
    };
  }

  /* ------------- Event wiring ----------------------------------- */
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
    runBtn.disabled = true;
    try {
      const { figure, config, mapping } = await fetchPlot(buildBody());
      applyMapping(figure, mapping);
      await Plotly.react(chart, figure.data, figure.layout, config);
    } catch (err) {
      console.error(err);
      alert("No se pudo cargar el gráfico: " + (err?.message || err));
    } finally {
      runBtn.disabled = false;
    }
  }

  form.addEventListener("submit", run);

  initPromise.then(() => {
    if (!runBtn.disabled) {
      run();
    }
  });
});
