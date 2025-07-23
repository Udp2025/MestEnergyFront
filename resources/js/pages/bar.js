/* ------------------------------------------------------------------ */
/*   Bar‑plot view – colour by, raw frequency handling, UX guards     */
/* ------------------------------------------------------------------ */
import { fetchPlot, applyMapping } from "../utils/plot";
import Plotly from "plotly.js-dist-min";

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
  const $ = (id) => document.getElementById(id);

  /* ----- DOM nodes ------------------------------------------------ */
  const runBtn = $("run");
  const form = $("plot-filters");
  const chartDiv = $("lineChart");
  if (!form || !chartDiv) {
    console.error("bar.js: required DOM nodes not found");
    return;
  }

  /* ----- Helper --------------------------------------------------- */
  const v = (name) => form[name]?.value?.trim() || DEFAULTS[name];

  /* ----- Payload builder ----------------------------------------- */
  function buildBody() {
    const metric = v("metric");
    const func = v("agg");
    const colorBy = v("colorBy");
    const freq = v("freq");
    const from = v("from");
    const to = v("to");
    const orient = v("orient");

    /* dynamic group‑by list (avoid duplicates) */
    const groupBy = ["site_id", "device_id"];

    /* aggregation clause (omit time_* keys for raw) */
    if (colorBy != DEFAULTS["colorBy"] && freq === null) {
      freq = "H";
    }
    const aggregation = [
      {
        group_by: groupBy,
        aggregations: { [metric]: [func] },
        ...(freq && { time_window: freq, time_column: "measurement_time" }),
      },
    ];

    return {
      table: "measurements",
      filter_map: {
        measurement_time: `[${from} 00:00:00, ${to} 23:59:59]`,
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
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (runBtn.disabled) return;
    runBtn.disabled = true;
    try {
      const { figure, config, mapping } = await fetchPlot(buildBody());
      applyMapping(figure, mapping);
      await Plotly.react(chartDiv, figure.data, figure.layout, config);
    } finally {
      runBtn.disabled = false;
    }
  });
});
