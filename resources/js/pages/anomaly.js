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
  metric: "power_w",
  from: HISTORY_START,
  to: TODAY,
  frequency: "H",
  agg: "sum",
  check_last: 24,
  pct_vote: 0.25,
  threshold: 0.5,
};

async function fetchAnomalies(body) {
  const res = await fetch("/proxy/ml/anomaly-detection", {
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
    const error = new Error("Anomaly API request failed");
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
  const chart = $("anomalyChart");
  const metaBox = $("anomalyMeta");
  const siteSel = $("site");
  const deviceSel = $("device");

  if (!form || !chart || !runBtn) {
    console.error("anomaly.js: required DOM nodes not found");
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
    const metric = v("metric", DEFAULTS.metric);
    const from = HISTORY_START;
    const to = TODAY;
    const checkLastRaw = Number(form["check_last"]?.value ?? DEFAULTS.check_last);
    const check_last =
      Number.isFinite(checkLastRaw) && checkLastRaw > 0
        ? checkLastRaw
        : DEFAULTS.check_last;
    const frequency = v("frequency", DEFAULTS.frequency);
    const agg = v("agg", DEFAULTS.agg);
    const pctVoteRaw = Number(form["pct_vote"]?.value ?? DEFAULTS.pct_vote);
    const thresholdRaw = Number(form["threshold"]?.value ?? DEFAULTS.threshold);

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
      filter_map: filterMap,
      check_last,
      metric_column: metric,
      frequency,
      time_column: "measurement_time",
      up_sampling_agg_func: agg,
      pct_vote_count:
        Number.isFinite(pctVoteRaw) && pctVoteRaw >= 0 && pctVoteRaw <= 1
          ? pctVoteRaw
          : DEFAULTS.pct_vote,
      threshold:
        Number.isFinite(thresholdRaw) && thresholdRaw >= 0 && thresholdRaw <= 1
          ? thresholdRaw
          : DEFAULTS.threshold,
    };
  }

  function updateMetadata(anomalies = [], requestBody, isAnomaly) {
    if (!metaBox) return;
    const countNode = metaBox.querySelector("[data-count]");
    const windowNode = metaBox.querySelector("[data-window]");
    const statusNode = metaBox.querySelector("[data-status]");

    if (!requestBody) {
      metaBox.hidden = true;
      return;
    }

    if (countNode) countNode.textContent = anomalies.length.toString();
    if (windowNode)
      windowNode.textContent = `${requestBody.check_last} ${
        requestBody.frequency === "H"
          ? "horas"
          : requestBody.frequency === "D"
          ? "días"
          : "semanas"
      }`;
    if (statusNode) {
      statusNode.textContent = isAnomaly ? "Anómalo" : "Normal";
      statusNode.classList.toggle("anomaly-meta__status--alert", !!isAnomaly);
      statusNode.classList.toggle("anomaly-meta__status--ok", !isAnomaly);
    }

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
      const response = await fetchAnomalies(requestBody);
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
      updateMetadata(response.anomalies, requestBody, response.is_anomaly);
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
