import Plotly from "plotly.js-dist-min";
import {
  attachNoticeTarget,
  normalisePlotError,
  plotIsEmpty,
  setupAdvancedFilters,
  csrfToken,
} from "../utils/plot";
import {
  canViewAllSites,
  currentUserSiteId,
  ensureAuthenticatedOrRedirect,
} from "../utils/auth";
import { fillSelect } from "../utils/list";
import { getSites, getDevices, fmtDate } from "../utils/core";

const TODAY = fmtDate(new Date());
const HISTORY_START = fmtDate(new Date(Date.now() - 30 * 24 * 60 * 60 * 1e3));
const DEFAULTS = {
  from: HISTORY_START,
  to: TODAY,
  horizon: 7,
  frequency: "D",
};

const FREQ_LABEL = {
  H: "horas",
  D: "días",
  W: "semanas",
  M: "meses",
  Q: "trimestres",
  Y: "años",
};

async function fetchForecast(body) {
  const res = await fetch("/ml/forecast", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": csrfToken(),
      Accept: "application/json",
    },
    body: JSON.stringify(body),
  });

  if (!res.ok) {
    let payload;
    try {
      payload = await res.json();
    } catch (_) {
      payload = await res.text();
    }
    const error = new Error("Forecast API request failed");
    error.status = res.status;
    error.payload = payload;
    error.isPlotError = true;
    throw error;
  }

  return res.json();
}

document.addEventListener("DOMContentLoaded", () => {
  ensureAuthenticatedOrRedirect();
  const $ = (id) => document.getElementById(id);

  const form = $("plot-filters");
  const runBtn = $("run");
  const chart = $("forecastChart");
  const metaBox = $("forecastMeta");
  const siteSel = $("site");
  const deviceSel = $("device");

  if (!form || !chart || !runBtn) {
    console.error("forecast.js: required DOM nodes not found");
    return;
  }

  setupAdvancedFilters(form);
  const notice = attachNoticeTarget(form);

  const isAdmin = canViewAllSites();
  let activeSiteId = currentUserSiteId();

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
      notice.show("Selecciona un sitio para ver sus dispositivos.", "info");
      return;
    }
    const rows = await getDevices(activeSiteId);
    fillSelect(deviceSel, rows, "device_id", "device_name");
    deviceSel.insertAdjacentHTML(
      "afterbegin",
      '<option value="ALL">Todos los dispositivos</option>'
    );
    deviceSel.value = "ALL";
    if (rows.length === 0) {
      notice.show("El sitio elegido no tiene dispositivos registrados.", "info");
    } else {
      notice.clear();
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
      const { message, severity } = normalisePlotError(error);
      notice.show(message, severity);
      runBtn.disabled = true;
      return;
    }
  })();

  const v = (name, fallback) => form[name]?.value?.trim() || fallback;

  function buildRequestBody() {
    const from = HISTORY_START;
    const to = TODAY;
    const horizonRaw = Number(form["horizon"]?.value ?? DEFAULTS.horizon);
    const horizon =
      Number.isFinite(horizonRaw) && horizonRaw > 0
        ? horizonRaw
        : DEFAULTS.horizon;
    const frequency = v("frequency", DEFAULTS.frequency);
    const includeConfInt = form["include_conf_int"]?.checked !== false;

    const filterMap = {
      measurement_time: `[${from} 00:00:00, ${to} 23:59:59]`,
    };
    if (activeSiteId && activeSiteId !== "ALL") {
      filterMap.site_id = [String(activeSiteId)];
    }
    if (deviceSel?.value && deviceSel.value !== "ALL") {
      filterMap.device_id = [String(deviceSel.value)];
    }

    return {
      table: "measurements",
      time_column: "measurement_time",
      target_column: "power_w",
      horizon,
      frequency: frequency || DEFAULTS.frequency,
      up_sampling_agg_func: "sum",
      include_conf_int: includeConfInt,
      filter_map: filterMap,
    };
  }

  function updateMetadata(metadata, frequency) {
    if (!metaBox) return;
    const model = metaBox.querySelector("[data-model]");
    const horizon = metaBox.querySelector("[data-horizon]");
    const runtime = metaBox.querySelector("[data-runtime]");

    if (!metadata) {
      metaBox.hidden = true;
      return;
    }

    const freqLabel = FREQ_LABEL[frequency] || frequency;
    if (model) model.textContent = metadata.model_name || "—";
    if (horizon)
      horizon.textContent = metadata.horizon
        ? `${metadata.horizon} ${freqLabel}`
        : "—";
    if (runtime)
      runtime.textContent = metadata.runtime_ms
        ? `${metadata.runtime_ms} ms`
        : "—";
    metaBox.hidden = false;
  }

  async function run(e) {
    e?.preventDefault();
    if (runBtn.disabled) return;
    notice.clear();

    const from = HISTORY_START;
    const to = TODAY;
    if (from > to) {
      notice.show(
        "Rango inválido: la fecha inicial es mayor que la final.",
        "error"
      );
      return;
    }

    runBtn.disabled = true;
    try {
      const requestBody = buildRequestBody();
      const response = await fetchForecast(requestBody);
      const figure = response.figure || { data: [], layout: {} };
      const config = {
        responsive: true,
        displaylogo: false,
        ...response.config,
      };

      if (figure.layout && figure.layout.title) {
        figure.layout.title = "";
      }
      figure.layout = {
        margin: { l: 50, r: 24, t: 24, b: 60 },
        autosize: true,
        ...(figure.layout || {}),
      };

      if (plotIsEmpty(figure)) {
        notice.show(
          "No se encontraron datos para los filtros seleccionados.",
          "info"
        );
      }

      await Plotly.react(chart, figure.data, figure.layout, config);
      updateMetadata(response.metadata, requestBody.frequency);
    } catch (err) {
      console.error(err);
      const { message, severity } = normalisePlotError(err);
      notice.show(message, severity);
      if (metaBox) metaBox.hidden = true;
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
