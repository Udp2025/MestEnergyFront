/*********************************************************************
 *  Heat-map page – site & device filters (Run button is the trigger)
 *********************************************************************/
import Plotly from "plotly.js-dist-min";
import debounce from "lodash.debounce";
import {
  fetchPlot,
  applyMapping,
  setupAdvancedFilters,
  attachNoticeTarget,
  normalisePlotError,
  plotIsEmpty,
} from "../utils/plot";
import { fillSelect } from "../utils/list";
import { getSites, getDevices } from "../utils/core";
import {
  canViewAllSites,
  currentUserSiteId,
  ensureAuthenticatedOrRedirect,
} from "../utils/auth";

const DAY_MS = 86_400_000;
const TODAY = new Date();

/* ---------- (x,y) → binning rules -------------------------------- */
const RULES = {
  "hour|weekday": { unit: "week", span: 7, tw: "H" },
  "weekday|hour": { unit: "week", span: 7, tw: "H" },
  "hour|day": { unit: "month", span: "dynamic", tw: "H" },
  "day|hour": { unit: "month", span: "dynamic", tw: "H" },
  "weekday|day": { unit: "week", span: 7, tw: "D" },
  "day|weekday": { unit: "week", span: 7, tw: "D" },
  "day|week": { unit: "month", span: "dynamic", tw: "D" },
  "week|day": { unit: "month", span: "dynamic", tw: "D" },
  "week|month": { unit: "year", span: 365, tw: "W" },
  "month|week": { unit: "year", span: 365, tw: "W" },
  "day|month": { unit: "year", span: 365, tw: "D" },
  "month|day": { unit: "year", span: 365, tw: "D" },
};

/* ---------- little helpers --------------------------------------- */
const iso = (d) => d.toISOString().slice(0, 10);
const thisMonday = (d) => {
  const t = new Date(d),
    w = t.getDay() || 7;
  t.setDate(t.getDate() - w + 1);
  return t;
};
const monthStart = (d) => new Date(d.getFullYear(), d.getMonth(), 1);
const daysInMonth = (d) =>
  new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();

document.addEventListener("DOMContentLoaded", async () => {
  ensureAuthenticatedOrRedirect();
  const $ = (id) => document.getElementById(id);

  /* ---- DOM ------------------------------------------------------ */
  const form = $("plot-filters");
  const chart = $("heatChart");
  const label = $("periodLabel");

  const runBtn = $("run");
  const prevBtn = $("prev");
  const nextBtn = $("next");

  if (!form || !chart || !runBtn) {
    console.error("heat_map.js: required DOM nodes not found");
    return;
  }

  setupAdvancedFilters(form);
  const notice = attachNoticeTarget(form);

  const axes = [...document.querySelectorAll(".axisSelect")];
  const siteSel = $("site"); // undefined for non-admins
  const deviceSel = $("device");

  /* ---- Site / device dropdowns ---------------------------------- */
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
      notice.show("Selecciona un sitio para cargar sus dispositivos.", "info");
      return;
    }
    const rows = await getDevices(activeSiteId);
    fillSelect(deviceSel, rows, "device_id", "device_name");
    deviceSel.insertAdjacentHTML(
      "afterbegin",
      '<option value="ALL">Todos los dispositivos</option>'
    );
    deviceSel.value = "ALL";
    const hasDevices = rows.length > 0;
    runBtn.disabled = !hasDevices;
    if (!hasDevices) {
      notice.show("El sitio elegido no tiene dispositivos registrados.", "info");
    } else {
      notice.clear();
    }
  }

  try {
    if (isAdmin) {
      await loadSites();
      siteSel.onchange = async () => {
        activeSiteId = siteSel.value;
        await loadDevices(); // no draw here
      };
    }
    if (!isAdmin && !activeSiteId) {
      throw new Error("El usuario no tiene un sitio asignado.");
    }

    await loadDevices();
  } catch (err) {
    console.error(err);
    const { message, severity } = normalisePlotError(err);
    notice.show(message, severity);
    [runBtn, prevBtn, nextBtn].forEach((b) => (b.disabled = true));
    return; // bail early
  }

  /* ---- axis-pair helpers ---------------------------------------- */
  const v = (name) => form[name]?.value;
  const key = () => `${v("x")}|${v("y")}`;
  const rule = () => RULES[key()];

  let periodStart = thisMonday(TODAY);

  function updateLabel() {
    const r = rule();
    if (!r) return;
    const span =
      r.span === "dynamic"
        ? r.unit === "month"
          ? daysInMonth(periodStart)
          : 1
        : r.span;

    const from = new Date(periodStart);
    const to = new Date(periodStart.getTime() + (span - 1) * DAY_MS);
    const fmt = (d) =>
      d.toLocaleDateString(undefined, {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });

    label.textContent =
      r.unit === "month"
        ? from.toLocaleDateString(undefined, { month: "long", year: "numeric" })
        : r.unit === "year"
        ? from.getFullYear()
        : `${fmt(from)} → ${fmt(to)}`;
  }

  function applyRule() {
    const r = rule();
    const invalid = !r;
    [runBtn, prevBtn, nextBtn].forEach((b) => (b.disabled = invalid));
    axes.forEach((sel) =>
      invalid
        ? (sel.title = "Combinación incompatible")
        : sel.removeAttribute("title")
    );

    if (invalid) {
      label.textContent = "Combinación inválida";
      notice.show("La combinación de ejes elegida no es válida.", "info");
      return;
    }

    if (!runBtn.disabled) {
      notice.clear();
    }

    if (r.unit === "week") periodStart = thisMonday(TODAY);
    if (r.unit === "day") periodStart = new Date(TODAY);
    if (r.unit === "month") periodStart = monthStart(TODAY);
    if (r.unit === "year") periodStart = new Date(TODAY.getFullYear(), 0, 1);

    updateLabel();
  }

  function shift(n) {
    /* prev / next period buttons */
    const r = rule();
    if (!r) return;

    if (r.unit === "day")
      periodStart = new Date(periodStart.getTime() + n * DAY_MS);
    if (r.unit === "week")
      periodStart = new Date(periodStart.getTime() + n * 7 * DAY_MS);
    if (r.unit === "month") {
      periodStart = new Date(periodStart);
      periodStart.setMonth(periodStart.getMonth() + n);
    }
    if (r.unit === "year") {
      periodStart = new Date(periodStart);
      periodStart.setFullYear(periodStart.getFullYear() + n);
    }

    updateLabel();
  }

  /* ---- build payload ------------------------------------------- */
  function buildBody() {
    const r = rule();
    const fn = v("agg") || "avg";
    const m = v("z");
    const span =
      r.span === "dynamic"
        ? r.unit === "month"
          ? daysInMonth(periodStart)
          : 1
        : r.span;

    const fromISO = iso(periodStart) + " 00:00:00";
    const toISO =
      iso(new Date(periodStart.getTime() + (span - 1) * DAY_MS)) + " 23:59:59";

    return {
      table: "measurements",
      filter_map: {
        measurement_time: `[${fromISO}, ${toISO}]`,
        ...(activeSiteId ? { site_id: "=" + activeSiteId } : {}),
        ...(deviceSel?.value && deviceSel.value !== "ALL"
          ? { device_id: "=" + deviceSel.value }
          : {}),
      },
      aggregation: [
        {
          aggregations: { [m]: [fn] },
          time_window: r.tw,
          time_column: "measurement_time",
        },
      ],
      chart: {
        chart_type: "heatmap",
        x: v("x"),
        y: v("y"),
        z: `${m}_${fn}`,
      },
    };
  }

  /* ---- draw (only on Run click) -------------------------------- */
  async function draw() {
    if (runBtn.disabled) return;
    runBtn.disabled = true;
    notice.clear();
    try {
      const { figure, config, mapping } = await fetchPlot(buildBody());
      applyMapping(figure, mapping);
      if (plotIsEmpty(figure)) {
        notice.show("No se encontraron datos para los filtros seleccionados.", "info");
      }
      await Plotly.react(chart, figure.data, figure.layout, config);
    } catch (err) {
      console.error(err);
      const { message, severity } = normalisePlotError(err);
      notice.show(message, severity);
    } finally {
      runBtn.disabled = false;
    }
  }

  /* ---- event wiring (no auto-draw) ------------------------------ */
  prevBtn.onclick = () => shift(-1);
  nextBtn.onclick = () => shift(+1);

  axes.forEach((sel) => (sel.onchange = applyRule));
  form.addEventListener("input", debounce(applyRule, 300)); // label refresh only
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    await draw();
  });

  applyRule(); // initial label & state
  if (!runBtn.disabled) {
    await draw();
  }
});
