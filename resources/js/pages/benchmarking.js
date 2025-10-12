/* ------------------------------------------------------------------ */
/*   Benchmarking bar plot – color by, frequency handling, UX guards  */
/* ------------------------------------------------------------------ */
import { fetchPlot, applyMapping, setupAdvancedFilters } from "../utils/plot";
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
  freq: "H", // ← default/raw
  orient: "v",
  agg: "sum",
  colorBy: "device_id",
};

/* ------------------------------------------------------------------ */
document.addEventListener("DOMContentLoaded", () => {
  ensureAuthenticatedOrRedirect();
  const $ = (id) => document.getElementById(id);

  /* ----- DOM nodes ------------------------------------------------ */
  const runBtn = $("run");
  const form = $("plot-filters");
  const chartDiv = $("lineChart");
  const siteSel = $("site");
  const deviceSel = $("device");
  if (!form || !chartDiv) {
    console.error("benchmarking.js: required DOM nodes not found");
    return;
  }

  setupAdvancedFilters(form);

  const isAdmin = canViewAllSites();
  let activeSiteId = isAdmin ? null : currentUserSiteId();

  async function loadSites() {
    if (!isAdmin || !siteSel) return;
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
        siteSel?.addEventListener("change", async () => {
          activeSiteId = siteSel.value;
          await loadDevices();
        });
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

  /* ----- Helper --------------------------------------------------- */
  const v = (name) => form[name]?.value?.trim() || DEFAULTS[name];

  /* ----- Payload builder ----------------------------------------- */
  function buildBody() {
    const metric = v("metric");
    const func = v("agg");
    const colorBy = v("colorBy");
    const freqRaw = v("freq");
    // allow future "no aggregation" by treating empty as null
    let timeWindow = freqRaw === "" ? null : freqRaw;
    const from = v("from");
    const to = v("to");
    const orient = v("orient");

    /* dynamic group‑by list (avoid duplicates) */
    const groupBy = ["site_id", "device_id"];

    /* aggregation clause (omit time_* keys for raw)
       If a temporal colour is requested but no time window provided,
       fall back to hourly to ensure derived columns exist. */
    if (colorBy !== DEFAULTS.colorBy && !timeWindow) {
      timeWindow = "H";
    }
    const aggregation = [
      {
        group_by: groupBy,
        aggregations: { [metric]: [func] },
        ...(timeWindow && {
          time_window: timeWindow,
          time_column: "measurement_time",
        }),
      },
    ];

    return {
      table: "measurements",
      filter_map: {
        measurement_time: `[${from} 00:00:00, ${to} 23:59:59]`,
        ...(deviceSel?.value && deviceSel.value !== "ALL"
          ? { device_id: "=" + deviceSel.value }
          : {}),
        ...(activeSiteId ? { site_id: "=" + activeSiteId } : {}),
      },
      aggregation,
      chart: {
        chart_type: "bar",
        x: "device_name",
        y: `${metric}_${func}`,
        style: { color: colorBy, orientation: orient },
      },
    };
  }

  /* ----- Submit --------------------------------------------------- */
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
      await Plotly.react(chartDiv, figure.data, figure.layout, config);
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
